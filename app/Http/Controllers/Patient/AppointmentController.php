<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

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

        // Format them exactly how your Blade file expects
        $appointments = $rawAppointments->map(function ($app) {
            $fullStart = $app->appointment_date . ' ' . $app->start_time;
            $fullEnd   = $app->appointment_date . ' ' . $app->end_time;

            return [
                'id'           => $app->id, // Needed for the Cancel dropdown later!
                'doctor_name'  => 'Dr. ' . $app->doctor_name,
                'type_label'   => $app->type === 'in_person' ? 'In-Person' : 'Telemedicine',
                'start_time'   => date('M d, Y g:i A', strtotime($fullStart)),
                'end_time'     => date('g:i A', strtotime($fullEnd)),
                'status_label' => ucfirst($app->status),
                'reason'       => $app->reason ?? 'N/A',
                // Raw date for the cancel filter
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
                'doctor_profiles.bio'
            )
            ->get();

        // --- 3. Generate the 7-Day Availability Structure ---
        $doctors = $rawDoctors->map(function ($doc) {
            $availability = [];
            
            // Loop through the next 7 days
            for ($i = 1; $i <= 7; $i++) {
                $date = \Carbon\Carbon::now()->addDays($i);
                
                $availability[] = [
                    'appointment_date' => $date->toDateString(), // "2026-06-25"
                    'label'            => $date->format('D, M d'), // "Thu, Jun 25"
                    
                    // NOTE: Passing a static array of slots for now so the UI works perfectly. 
                    // Later, we can plug in our decodeSlotMask() helper here!
                    'slots'            => ['09:00', '10:00', '13:00', '15:00'] 
                ];
            }

            return [
                'id'           => $doc->id,
                'name'         => 'Dr. ' . $doc->name,
                'specialty'    => $doc->specialty,
                'bio'          => $doc->bio ?? 'No bio available.',
                'theme'        => 'light', // Matches your Alpine :class binding
                'availability' => $availability
            ];
        })->toArray();


        // --- 4. Return the View ---
        return view('patient.appointment', compact('appointments', 'doctors'));
    }

    public function book(Request $request)
    {
        $patientId = Session::get('user_id');

        $request->validate([
            'doctor_id'        => 'required|exists:users,id',
            'appointment_date' => 'required|date',
            'slot'             => 'required|string',
            'reason'           => 'nullable|string',
            'appointment_type' => 'required|in:in_person,telemedicine',
        ]);


        $timeString = $request->appointment_date . ' ' . $request->slot;
        $startTs = strtotime($timeString);
        $endTs   = $startTs + (30 * 60);

        DB::table('appointments')->insert([
            'patient_id'        => $patientId,
            'doctor_id'         => $request->doctor_id,
            'appointment_date'  => $request->appointment_date,
            'start_time'        => date('H:i:s', $startTs),
            'end_time'          => date('H:i:s', $endTs),
            'slot_mask'         => 0,
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
}