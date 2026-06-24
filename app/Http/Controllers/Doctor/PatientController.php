<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function getPatients()
    {
        $doctorId = Session::get('user_id');
        $today = now()->toDateString();

        $todayPatients = $this->fetchAppointmentRows($doctorId, 'today', $today);
        $upcomingPatients = $this->fetchAppointmentRows($doctorId, 'upcoming', $today);

        return view('doctor.patients.index', compact('todayPatients', 'upcomingPatients'));
    }

    public function updateEncounter(Request $request, string $uuid)
    {
        $doctorId = Session::get('user_id');

        $appointment = DB::table('appointments')
            ->where('uuid', $uuid)
            ->where('doctor_id', $doctorId)
            ->first();

        if (!$appointment) {
            abort(404, 'Appointment not found.');
        }

        $validated = $request->validate([
            'chief_complaint' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:5000',
            'drug_name' => 'nullable|string|max:255',
            'dosage' => 'nullable|string|max:100',
        ]);

        try {
            DB::transaction(function () use ($doctorId, $appointment, $validated) {
                $record = DB::table('medical_records')
                    ->where('appointment_id', $appointment->id)
                    ->first();

                if ($record) {
                    DB::table('medical_records')->where('id', $record->id)->update([
                        'chief_complaint' => $validated['chief_complaint'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'updated_at' => now(),
                    ]);
                    $recordId = $record->id;
                } else {
                    $recordId = DB::table('medical_records')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'patient_id' => $appointment->patient_id,
                        'doctor_id' => $doctorId,
                        'appointment_id' => $appointment->id,
                        'record_date' => $appointment->appointment_date,
                        'chief_complaint' => $validated['chief_complaint'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if (!empty($validated['drug_name'])) {
                    $existingRx = DB::table('prescriptions')
                        ->where('medical_record_id', $recordId)
                        ->orderBy('issued_at', 'desc')
                        ->first();

                    if ($existingRx) {
                        DB::table('prescriptions')->where('id', $existingRx->id)->update([
                            'drug_name' => $validated['drug_name'],
                            'dosage' => $validated['dosage'] ?? $existingRx->dosage,
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('prescriptions')->insert([
                            'uuid' => (string) Str::uuid(),
                            'medical_record_id' => $recordId,
                            'patient_id' => $appointment->patient_id,
                            'doctor_id' => $doctorId,
                            'drug_name' => $validated['drug_name'],
                            'dosage' => $validated['dosage'] ?? '—',
                            'frequency' => 'As directed',
                            'status' => 'active',
                            'issued_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

            return redirect()->route('doctor.patients.index')
                ->with('success', 'Patient encounter updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update encounter. Please try again.'])
                ->withInput();
        }
    }

    public function confirmAppointment($id)
    { 
        $doctorId = Session::get('user_id');

        // Security Check: Make sure this appointment actually belongs to the logged-in doctor
        $appointment = DB::table('appointments')
            ->where('id', $id)
            ->where('doctor_id', $doctorId)
            ->first();

        if (!$appointment) {
            return redirect()->back()->withErrors(['error' => 'Unauthorized action or appointment not found.']);
        }
        if ($appointment->status !== 'pending') {
            return redirect()->back()->withErrors(['error' => 'Appointment is already confirmed or cannot be changed.']);
        }

        // Update the status to confirmed
        DB::table('appointments')
            ->where('id', $id)
            ->update([
                'status' => 'confirmed',
                'updated_at' => now(),
            ]);

        return redirect()->route('doctor.dashboard')->with('success', 'Appointment successfully confirmed.');
    }
    public function cancelAppointment($id)
    { 
        $doctorId = Session::get('user_id');

        // Security Check: Make sure this appointment actually belongs to the logged-in doctor
        $appointment = DB::table('appointments')
            ->where('id', $id)
            ->where('doctor_id', $doctorId)
            ->first();

        if (!$appointment) {
            return redirect()->back()->withErrors(['error' => 'Unauthorized action or appointment not found.']);
        }
        if ($appointment->status !== 'pending'){
            return redirect()->back()->withErrors(['error' => 'Cannot cancel this appointment']);
        }

        // Update the status to confirmed
        DB::table('appointments')
            ->where('id', $id)
            ->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);

        return redirect()->route('doctor.dashboard')->with('success', 'Appointment successfully confirmed.');
    }

    private function fetchAppointmentRows(int $doctorId, string $scope, string $today)
    {
        $query = DB::table('appointments')
            ->join('users as patients', 'appointments.patient_id', '=', 'patients.id')
            ->leftJoin('medical_records', 'medical_records.appointment_id', '=', 'appointments.id')
            ->where('appointments.doctor_id', $doctorId)
            ->whereIn('appointments.status', ['confirmed', 'pending', 'completed'])
            ->select(
                'appointments.uuid',
                'appointments.type',
                'appointments.start_time',
                'appointments.end_time',
                'appointments.appointment_date',
                'patients.name as patient_name',
                'medical_records.chief_complaint',
                'medical_records.notes',
                DB::raw('(SELECT drug_name FROM prescriptions WHERE medical_record_id = medical_records.id ORDER BY issued_at DESC LIMIT 1) as prescription_name'),
                DB::raw('(SELECT dosage FROM prescriptions WHERE medical_record_id = medical_records.id ORDER BY issued_at DESC LIMIT 1) as prescription_dosage')
            );

        if ($scope === 'today') {
            $query->where('appointments.appointment_date', $today)
                ->orderBy('appointments.start_time');
        } else {
            $query->where('appointments.appointment_date', '>', $today)
                ->orderBy('appointments.appointment_date')
                ->orderBy('appointments.start_time');
        }

        return $query->get()->map(function ($row) {
            $start = Carbon::parse($row->start_time);
            $end = Carbon::parse($row->end_time);

            return (object) [
                'uuid' => $row->uuid,
                'patient_name' => $row->patient_name,
                'type_label' => $row->type === 'telemedicine' ? 'Telemedicine' : 'In Person',
                'start_time' => $start->format('g:i A'),
                'end_time' => $end->format('g:i A'),
                'chief_complaint' => $row->chief_complaint,
                'notes' => $row->notes,
                'prescription' => $row->prescription_name
                    ? trim($row->prescription_name . ($row->prescription_dosage ? ' ' . $row->prescription_dosage : ''))
                    : null,
                'drug_name' => $row->prescription_name,
                'dosage' => $row->prescription_dosage,
            ];
        });
    }
}
