<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function index()
    {
        $patientId = Session::get('user_id');

        $appointmentsData = DB::table('appointments')
            ->join('users', 'appointments.doctor_id', '=', 'users.id')
            ->where('appointments.patient_id', $patientId)
            ->select(
                'appointments.id',
                'users.name as doctor_name',
                'appointments.type',
                'appointments.appointment_date',
                'appointments.start_time',
                'appointments.end_time',
                'appointments.status',
                'appointments.reason'
            )
            ->orderBy('appointments.appointment_date', 'desc')
            ->orderBy('appointments.start_time', 'desc')
            ->get();

        $appointments = $appointmentsData->map(function ($app) {
            $fullStartStr = $app->appointment_date . ' ' . $app->start_time;
            $fullEndStr   = $app->appointment_date . ' ' . $app->end_time;

            return [
                'id' => $app->id,
                'doctor_name'       => 'Dr. ' . $app->doctor_name,
                'type_label'        => $app->type === 'in_person' ? 'In-Person' : 'Telemedicine',
                'start_time'        => date('M d, Y g:i A', strtotime($fullStartStr)),
                'end_time'          => date('g:i A', strtotime($fullEndStr)),
                'status_label'      => ucfirst($app->status),
                'reason'            => $app->reason ?? 'Routine Checkup',
            ];
        })->toArray();

        $doctorsData = DB::table('users')
            ->join('doctor_profiles', 'users.id', '=', 'doctor_profiles.user_id')
            ->join('specialties', 'doctor_profiles.specialty_id', '=', 'specialties.id')
            ->where('users.role', 'doctor')
            ->select(
                'users.id',
                'users.name',
                'specialties.name as specialty',
                'doctor_profiles.bio'
            )
            ->get();

        $doctors = $doctorsData->map(function ($doc, $index) {
            return [
                'id'            => $doc->id,
                'name'          > 'Dr. ' . $doc->name,
                'specialty'     => $doc->specialty,
                'bio'           => $doc->bio ?? 'No biography provided.',
                'theme'         => $index % 2 === 0 ? 'dark' : 'light', 
                'availability'  => $this->generateMockAvailability()
            ];
        })->toArray();

        return view('appointments', compact('appointments', 'doctors'));
    }

    public function getPatientAppointments()
    {
        $patientId = Session::get('user_id');

        // --- 1. Fetch the Patient's Appointments ---
        $rawAppointments = DB::table('appointments')
            ->join('users as doctors', 'appointments.doctor_id', '=', 'doctors.id')
            ->where('appointments.patient_id', $patientId)
            ->select(
                'appointments.*', 
                'doctors.name as doctor_name'
            )
            ->orderBy('appointments.appointment_date', 'asc')
            ->orderBy('appointments.start_time', 'asc')
            ->get();

        $appointments = $rawAppointments->map(function ($app) {
            $fullStart = $app->appointment_date . ' ' . $app->start_time;
            $fullEnd   = $app->appointment_date . ' ' . $app->end_time;

            return [
                'id'             => $app->id,
                'doctor_name'    => 'Dr. ' . $app->doctor_name,
                'type_label'     => $app->type === 'in_person' ? 'In-Person' : 'Telemedicine',
                'start_time'     => date('M d, Y g:i A', strtotime($fullStart)),
                'end_time'       => date('g:i A', strtotime($fullEnd)),
                'status_label'   => ucfirst($app->status),
                'reason'         => $app->reason ?? 'N/A',
                'is_cancellable' => $app->status === 'pending' || $app->status === 'confirmed'
            ];
        })->toArray();

        // --- 2. Fetch the Doctor Directory ---
        $rawDoctors = DB::table('users')
            ->join('doctor_profiles', 'users.id', '=', 'doctor_profiles.user_id')
            ->join('specialties', 'doctor_profiles.specialty_id', '=', 'specialties.id')
            ->where('users.role', 'doctor')
            ->select(
                'users.id', 
                'users.name', 
                'specialties.name as specialty', 
                'doctor_profiles.bio',
                'doctor_profiles.slot_duration_minutes' // CRITICAL: We need to know their interval!
            )
            ->get();

        // --- PRE-FETCH DATA TO PREVENT N+1 QUERY CRASH ---
        $startDate = \Carbon\Carbon::now()->addDay()->toDateString(); // Start tomorrow
        $endDate   = \Carbon\Carbon::now()->addDays(7)->toDateString();

        // Grab ALL schedules in one query
        $allSchedules = DB::table('doctor_schedules')->get()->groupBy('doctor_id');

        // Grab ALL active appointments in the next 7 days in one query
        $allBookings = DB::table('appointments')
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->groupBy(function($app) {
                // Group by Doctor AND Date so we can find them instantly
                return $app->doctor_id . '_' . $app->appointment_date; 
            });

        // --- 3. Generate the 7-Day Availability Structure ---
        $doctors = $rawDoctors->map(function ($doc) use ($allSchedules, $allBookings) {
            $availability = [];
            $duration = $doc->slot_duration_minutes ?? 30; // Fallback just in case
            
            for ($i = 1; $i <= 7; $i++) {
                $dateObj = \Carbon\Carbon::now()->addDays($i);
                $dateStr = $dateObj->toDateString();
                $weekday = $dateObj->dayOfWeek; // 0 (Sun) to 6 (Sat)
                
                // A. Find Master Mask
                $docSchedules = $allSchedules->get($doc->id) ?? collect();
                $daySchedule  = $docSchedules->firstWhere('weekday', $weekday);
                $masterMask   = $daySchedule ? (int) $daySchedule->slot_mask : 0;

                // B. Find Booked Mask
                $bookedMask = 0;
                $dailyBookings = $allBookings->get($doc->id . '_' . $dateStr) ?? collect();
                
                foreach ($dailyBookings as $booking) {
                    $bookedMask |= $this->calculateSlotMask([
                        ['start' => $booking->start_time, 'end' => $booking->end_time]
                    ]);
                }

                // C. The Bitwise Engine: Master AND NOT Booked
                $availableMask = $masterMask & ~$bookedMask;

                // D. Decode the remaining binary into actual time buttons for Alpine!
                $slots = [];
                if ($availableMask > 0) {
                    $slots = $this->decodeMaskToSlots($availableMask, $duration);
                }
                
                $availability[] = [
                    'appointment_date' => $dateStr,
                    'label'            => $dateObj->format('D, M d'),
                    'slots'            => $slots 
                ];
            }

            return [
                'id'           => $doc->id,
                'name'         => 'Dr. ' . $doc->name,
                'specialty'    => $doc->specialty,
                'bio'          => $doc->bio ?? 'No bio available.',
                'theme'        => 'light', 
                'availability' => $availability
            ];
        })->toArray();

        // --- 4. Return the View ---
        return view('patient.appointment', compact('appointments', 'doctors'));
    }

    public function isSlotAvailable($doctorId, $date, $startTime, $durationMinutes)
    {
        // 1. Get the doctor's base schedule for this day of the week (0 = Sunday, 1 = Monday, etc.)
        $dayOfWeek = date('w', strtotime($date));
        $schedule = DB::table('doctor_schedules')
            ->where('doctor_id', $doctorId)
            ->where('weekday', $dayOfWeek)
            ->first();

        // If no schedule exists, or the mask is 0, the doctor isn't working today.
        if (!$schedule || $schedule->slot_mask == 0) {
            return false; 
        }

        $masterMask = (int) $schedule->slot_mask;

        // 2. Build the Booked Mask from existing appointments on this specific date
        $bookedAppointments = DB::table('appointments')
            ->where('doctor_id', $doctorId)
            ->where('appointment_date', $date)
            ->whereIn('status', ['pending', 'confirmed']) // Ignore cancelled appointments!
            ->get();

        $bookedMask = 0;
        foreach ($bookedAppointments as $appt) {
            // Reuse your existing helper to convert the saved times back into a mask
            $apptMask = $this->calculateSlotMask([
                ['start' => $appt->start_time, 'end' => $appt->end_time]
            ]);
            $bookedMask = $bookedMask | $apptMask; // Merge it into the booked pool
        }

        // 3. Calculate Actual Available Time (Master AND NOT Booked)
        $availableMask = $masterMask & ~$bookedMask;

        // 4. Calculate the mask for what the patient actually requested
        $requestedEndTime = date('H:i:s', strtotime($startTime . " + $durationMinutes minutes"));
        $requestedMask = $this->calculateSlotMask([
            ['start' => $startTime, 'end' => $requestedEndTime]
        ]);

        // 5. The Collision Check: Does the available mask fully cover the requested mask?
        return ($availableMask & $requestedMask) === $requestedMask;
    }

    public function bookAppointment(Request $request)
    {
        $patientId = Session::get('user_id');

        // 1. Validate the correct input names
        $request->validate([
            'doctor_id'        => 'required|exists:users,id',
            'appointment_date' => 'required|date',
            'start_time'       => 'required|date_format:H:i', // Alpine sends "09:00"
            'reason'           => 'nullable|string',
            'appointment_type' => 'required|in:in_person,telemedicine',
        ]);

        // 2. Fetch the specific doctor's rules
        $doctorProfile = DB::table('doctor_profiles')
            ->where('user_id', $request->doctor_id)
            ->first();
            
        $durationMinutes = $doctorProfile->slot_duration_minutes ?? 30;

        // 3. SECURITY CHECK: Ensure it hasn't been taken in the last few seconds!
        $isAvailable = $this->isSlotAvailable(
            $request->doctor_id, 
            $request->appointment_date, 
            $request->start_time, 
            $durationMinutes
        );

        if (!$isAvailable) {
            return redirect()->back()->withErrors([
                'error' => 'Sorry, this time slot was just taken by another patient. Please choose another one.'
            ]);
        }

        // 4. Calculate the true end time dynamically
        $startTs = strtotime($request->appointment_date . ' ' . $request->start_time);
        $endTs   = $startTs + ($durationMinutes * 60);

        // (Optional) Calculate the mask if your DB schema requires it
        $slotMask = $this->calculateSlotMask([
            ['start' => $request->start_time, 'end' => date('H:i', $endTs)]
        ]);

        // 5. Save it to the database
        DB::table('appointments')->insert([
            'patient_id'        => $patientId,
            'doctor_id'         => $request->doctor_id,
            'appointment_date'  => $request->appointment_date,
            'start_time'        => date('H:i:s', $startTs),
            'end_time'          => date('H:i:s', $endTs),
            'slot_mask'         => $slotMask, 
            'type'              => $request->appointment_type,
            'reason'            => $request->reason,
            'status'            => 'pending',
        ]);

        return redirect()->route('patient.appointments')->with('success', 'Appointment successfully requested.');
    }

    public function cancel(Request $request)
    {
        $patientId = Session::get('user_id');
        
        DB::table('appointments')
            ->where('id', $request->input('appointment_id'))
            ->where('patient_id', $patientId)
            ->update(['status' => 'cancelled']);

        return redirect()->route('patient.appointments')->with('success', 'Appointment has been cancelled.');
    }

    private function generateMockAvailability()
    {
        $availability = [];
        for ($i = 1; $i <= 3; $i++) {
            $ts = strtotime("+$i days");
            $availability[] = [
                'appointment_date'  => date('Y-m-d', $ts),
                'label'             => date('l, M d', $ts),
                'slots'             => ['09:00 AM', '10:30 AM', '02:00 PM', '03:30 PM']
            ];
        }
        return $availability;
    }

    private function decodeMaskToSlots(int $availableMask, int $durationMinutes): array
    {
        $slots = [];
        $current = \Carbon\Carbon::createFromTimeString('08:00:00');
        $end = \Carbon\Carbon::createFromTimeString('17:00:00');

        // Step through the day in chunks based on the doctor's specific duration
        while ($current->copy()->addMinutes($durationMinutes)->lte($end)) {
            $startTime = $current->format('H:i');
            $endTime = $current->copy()->addMinutes($durationMinutes)->format('H:i');

            // Generate what the mask WOULD be for this specific chunk
            $requestedMask = $this->calculateSlotMask([
                ['start' => $startTime, 'end' => $endTime]
            ]);

            // Collision Check: Does the available time completely cover this chunk?
            if (($availableMask & $requestedMask) === $requestedMask) {
                $slots[] = $startTime; // It's free! Send it to the frontend.
            }

            $current->addMinutes($durationMinutes);
        }

        return $slots;
    }
    private function calculateSlotMask(array $timeRanges): int
    {
        $baseTime = Carbon::createFromTimeString('08:00:00');
        $dailyMask = 0;

        foreach ($timeRanges as $range) {
            $startTime = Carbon::createFromTimeString($range['start']);
            $endTime = Carbon::createFromTimeString($range['end']);
            $startBit = $baseTime->diffInMinutes($startTime) / 15;
            $durationBits = $startTime->diffInMinutes($endTime) / 15;
            $chunkMask = ((1 << $durationBits) - 1) << $startBit;
            $dailyMask = $dailyMask | $chunkMask;
        }

        return $dailyMask;
    }
}