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
                'is_cancellable' => $app->status === 'pending'
            ];
        })->toArray();

        $rawDoctors = DB::table('users')
            ->join('doctor_profiles', 'users.id', '=', 'doctor_profiles.user_id')
            ->join('specialties', 'doctor_profiles.specialty_id', '=', 'specialties.id')
            ->where('users.role', 'doctor')
            ->select(
                'users.id', 
                'users.name', 
                'specialties.name as specialty', 
                'doctor_profiles.bio',
                'doctor_profiles.slot_duration_minutes'
            )
            ->get();

        $startDate = \Carbon\Carbon::now()->addDay()->toDateString(); 
        $endDate   = \Carbon\Carbon::now()->addDays(7)->toDateString();

        $allSchedules = DB::table('doctor_schedules')->get()->groupBy('doctor_id');

        $allBookings = DB::table('appointments')
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->groupBy(function($app) {
                return $app->doctor_id . '_' . $app->appointment_date; 
            });

        $doctors = $rawDoctors->map(function ($doc) use ($allSchedules, $allBookings) {
            $availability = [];
            $duration = $doc->slot_duration_minutes ?? 30; 
            
            for ($i = 1; $i <= 7; $i++) {
                $dateObj = \Carbon\Carbon::now()->addDays($i);
                $dateStr = $dateObj->toDateString();
                $weekday = $dateObj->dayOfWeek; 
                
                $docSchedules = $allSchedules->get($doc->id) ?? collect();
                $daySchedule  = $docSchedules->firstWhere('weekday', $weekday);
                $masterMask   = $daySchedule ? (int) $daySchedule->slot_mask : 0;

                $bookedMask = 0;
                $dailyBookings = $allBookings->get($doc->id . '_' . $dateStr) ?? collect();
                
                foreach ($dailyBookings as $booking) {
                    $bookedMask |= $this->calculateSlotMask([
                        ['start' => $booking->start_time, 'end' => $booking->end_time]
                    ]);
                }

                $availableMask = $masterMask & ~$bookedMask;

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

        return view('patient.appointment', compact('appointments', 'doctors'));
    }

    public function isSlotAvailable($doctorId, $date, $startTime, $durationMinutes)
    {
        $dayOfWeek = date('w', strtotime($date));
        $schedule = DB::table('doctor_schedules')
            ->where('doctor_id', $doctorId)
            ->where('weekday', $dayOfWeek)
            ->first();

        if (!$schedule || $schedule->slot_mask == 0) {
            return false; 
        }

        $masterMask = (int) $schedule->slot_mask;

        $bookedAppointments = DB::table('appointments')
            ->where('doctor_id', $doctorId)
            ->where('appointment_date', $date)
            ->whereIn('status', ['pending', 'confirmed']) 
            ->get();

        $bookedMask = 0;
        foreach ($bookedAppointments as $appt) {
            $apptMask = $this->calculateSlotMask([
                ['start' => $appt->start_time, 'end' => $appt->end_time]
            ]);
            $bookedMask = $bookedMask | $apptMask; 
        }

        $availableMask = $masterMask & ~$bookedMask;

        $requestedEndTime = date('H:i:s', strtotime($startTime . " + $durationMinutes minutes"));
        $requestedMask = $this->calculateSlotMask([
            ['start' => $startTime, 'end' => $requestedEndTime]
        ]);

        return ($availableMask & $requestedMask) === $requestedMask;
    }

    public function bookAppointment(Request $request)
    {
        $patientId = Session::get('user_id');

        $request->validate([
            'doctor_id'        => 'required|exists:users,id',
            'appointment_date' => 'required|date',
            'start_time'       => 'required|date_format:H:i', 
            'reason'           => 'nullable|string',
            'appointment_type' => 'required|in:in_person,telemedicine',
        ]);

        $doctorProfile = DB::table('doctor_profiles')
            ->where('user_id', $request->doctor_id)
            ->first();
            
        $durationMinutes = $doctorProfile->slot_duration_minutes ?? 30;

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

        $startTs = strtotime($request->appointment_date . ' ' . $request->start_time);
        $endTs   = $startTs + ($durationMinutes * 60);

        $slotMask = $this->calculateSlotMask([
            ['start' => $request->start_time, 'end' => date('H:i', $endTs)]
        ]);

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

        while ($current->copy()->addMinutes($durationMinutes)->lte($end)) {
            $startTime = $current->format('H:i');
            $endTime = $current->copy()->addMinutes($durationMinutes)->format('H:i');

            $requestedMask = $this->calculateSlotMask([
                ['start' => $startTime, 'end' => $endTime]
            ]);

            if (($availableMask & $requestedMask) === $requestedMask) {
                $slots[] = $startTime;
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