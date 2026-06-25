<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PatientController extends Controller
{
    public function getPatients()
    {
        $doctorId = Session::get('user_id');
        $today    = now()->toDateString();
        
        Log::info('Doctor patients page loaded', [
            'doctor_id' => $doctorId,
            'today'     => $today,
        ]);

        $todayPatients    = $this->fetchAppointmentRows($doctorId, 'today',    $today);
        $upcomingPatients = $this->fetchAppointmentRows($doctorId, 'upcoming', $today);
 
        Log::info('Patient lists fetched', [
            'doctor_id'        => $doctorId,
            'today_count'      => $todayPatients->count(),
            'upcoming_count'   => $upcomingPatients->count(),
        ]);
 
        return view('doctor.patients.index', compact('todayPatients', 'upcomingPatients'));
    }


    public function updateEncounter(Request $request, string $uuid)
    {
        $doctorId = Session::get('user_id');

        Log::info('Encounter update requested', [
            'doctor_id'        => $doctorId,
            'appointment_uuid' => $uuid
        ]);

 
        $appointment = DB::table('appointments')
            ->where('uuid', $uuid)
            ->where('doctor_id', $doctorId)
            ->first();
 
        if (!$appointment) {
            Log::warning('Encounter update failed: appointment not found or unauthorized', [
                'doctor_id'        => $doctorId,
                'appointment_uuid' => $uuid,
            ]);

            abort(404, 'Appointment not found.');
        }
 
        $validated = $request->validate([
            'chief_complaint'                => 'nullable|string|max:1000',
            'diagnosis'                      => 'nullable|string|max:1000',
            'notes'                          => 'nullable|string|max:5000',
            'vitals'                         => 'nullable|array',
            'vitals.bp'                      => 'nullable|string|max:20',
            'vitals.hr'                      => 'nullable|numeric',
            'vitals.temp_c'                  => 'nullable|numeric',
            'vitals.weight_kg'               => 'nullable|numeric',
            'prescriptions'                  => 'nullable|array',
            'prescriptions.*.drug_name'      => 'required_with:prescriptions|string|max:255',
            'prescriptions.*.dosage'         => 'required_with:prescriptions|string|max:100',
            'prescriptions.*.frequency'      => 'required_with:prescriptions|string|max:100',
            'prescriptions.*.duration'       => 'nullable|string|max:100',
            'prescriptions.*.instructions'   => 'nullable|string|max:1000',
            'prescriptions.*.valid_until'    => 'nullable|date',
        ]);
 
        try {
            DB::transaction(function () use ($doctorId, $appointment, $validated) {

                DB::table('appointments')->where('id', $appointment->id)->update([
                    'status'     => 'completed',
                    'updated_at' => now(),
                ]);

                Log::info('Encounter: appointment marked completed', [
                    'doctor_id'      => $doctorId,
                    'appointment_id' => $appointment->id,
                ]);

                $vitalsJson = !empty($validated['vitals'])
                    ? json_encode(array_filter($validated['vitals'], fn($v) => $v !== '' && $v !== null))
                    : null;
 
                $record = DB::table('medical_records')
                    ->where('appointment_id', $appointment->id)
                    ->first();
 
                if ($record) {
                    DB::table('medical_records')
                        ->where('id', $record->id)
                        ->update([
                            'chief_complaint' => $validated['chief_complaint'] ?? null,
                            'diagnosis'       => $validated['diagnosis']       ?? null,
                            'notes'           => $validated['notes']           ?? null,
                            'vitals'          => $vitalsJson,
                            'updated_at'      => now(),
                        ]);
                    $recordId = $record->id;

                    Log::info('Encounter: medical record updated', [
                        'doctor_id' => $doctorId,
                        'record_id' => $recordId,
                    ]);

                } else {
                    $recordId = DB::table('medical_records')->insertGetId([
                        'uuid'            => (string) Str::uuid(),
                        'patient_id'      => $appointment->patient_id,
                        'doctor_id'       => $doctorId,
                        'appointment_id'  => $appointment->id,
                        'record_date'     => $appointment->appointment_date,
                        'chief_complaint' => $validated['chief_complaint'] ?? null,
                        'diagnosis'       => $validated['diagnosis']       ?? null,
                        'notes'           => $validated['notes']           ?? null,
                        'vitals'          => $vitalsJson,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    Log::info('Encounter: medical record created', [
                        'doctor_id'      => $doctorId,
                        'record_id'      => $recordId,
                        'appointment_id' => $appointment->id,
                    ]);

                }
 
                // ── Replace prescriptions ──
                // Delete existing ones for this record and re-insert from form
                if (!empty($validated['prescriptions'])) {
                    DB::table('prescriptions')
                        ->where('medical_record_id', $recordId)
                        ->delete();
                    
                    Log::info('Encounter: existing prescriptions deleted', [
                        'doctor_id' => $doctorId,
                        'record_id' => $recordId
                    ]);

 
                    $rxRows = [];
                    foreach ($validated['prescriptions'] as $rx) {
                        if (empty($rx['drug_name'])) continue;
 
                        $rxRows[] = [
                            'uuid'              => (string) Str::uuid(),
                            'medical_record_id' => $recordId,
                            'patient_id'        => $appointment->patient_id,
                            'doctor_id'         => $doctorId,
                            'drug_name'         => $rx['drug_name'],
                            'dosage'            => $rx['dosage'],
                            'frequency'         => $rx['frequency'],
                            'duration'          => $rx['duration']      ?? null,
                            'instructions'      => $rx['instructions']  ?? null,
                            'valid_until'       => $rx['valid_until']   ?? null,
                            'status'            => 'active',
                            'issued_at'         => now(),
                            'updated_at'        => now(),
                        ];
                    }
 
                    if (!empty($rxRows)) {
                        DB::table('prescriptions')->insert($rxRows);

                        Log::info('Encounter: prescriptions inserted', [
                            'doctor_id' => $doctorId,
                            'record_id' => $recordId,
                            'count'     => count($rxRows),
                            'drugs'     => array_column($rxRows, 'drug_name'),
                        ]);

                    }
                }
            });

            Log::info('Encounter update completed successfully', [
                'doctor_id'        => $doctorId,
                'appointment_uuid' => $uuid,
            ]);

 
            return redirect()
                ->route('doctor.patients.index')
                ->with('success', 'Encounter updated successfully.');
 
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }


    public function confirmAppointment($id)
    {
        $doctorId = Session::get('user_id');

        Log::info('Confirm appointment requested', [
            'doctor_id'      => $doctorId,
            'appointment_id' => $id,
        ]);

 
        $appointment = DB::table('appointments')
            ->where('id', $id)
            ->where('doctor_id', $doctorId)
            ->first();
 
        if (!$appointment) {
            Log::warning('Confirm failed: appointment not found or unauthorized', [
                'doctor_id'      => $doctorId,
                'appointment_id' => $id,
            ]);
            return back()->withErrors(['error' => 'Unauthorized or appointment not found.']);
        }
        if ($appointment->status !== 'pending') {
            Log::warning('Confirm failed: appointment not in pending status', [
                'doctor_id'      => $doctorId,
                'appointment_id' => $id,
                'status'         => $appointment->status,
            ]);

            return back()->withErrors(['error' => 'Appointment cannot be confirmed.']);
        }
 
        DB::table('appointments')->where('id', $id)->update([
            'status'     => 'confirmed',
            'updated_at' => now(),
        ]);

        Log::info('Appointment confirmed successfully', [
            'doctor_id'      => $doctorId,
            'appointment_id' => $id,
        ]);

 
        return redirect()->route('doctor.dashboard')->with('success', 'Appointment confirmed.');
    }

    public function cancelAppointment($id)
    {
        $doctorId = Session::get('user_id');

        Log::info('Cancel appointment requested', [
            'doctor_id'      => $doctorId,
            'appointment_id' => $id,
        ]);
 
        $appointment = DB::table('appointments')
            ->where('id', $id)
            ->where('doctor_id', $doctorId)
            ->first();
 
        if (!$appointment) {
            Log::warning('Cancel failed: appointment not found or unauthorized', [
                'doctor_id'      => $doctorId,
                'appointment_id' => $id,
            ]);

            return back()->withErrors(['error' => 'Unauthorized or appointment not found.']);
        }
        if (!in_array($appointment->status, ['pending', 'confirmed'])) {
            Log::warning('Cancel failed: appointment not in cancellable status', [
                'doctor_id'      => $doctorId,
                'appointment_id' => $id,
                'status'         => $appointment->status,
            ]);
            return back()->withErrors(['error' => 'Cannot cancel this appointment.']);
        }
 
        DB::table('appointments')->where('id', $id)->update([
            'status'     => 'cancelled',
            'updated_at' => now(),
        ]);

        Log::info('Appointment cancelled successfully', [
            'doctor_id'      => $doctorId,
            'appointment_id' => $id,
        ]);

 
        return redirect()->route('doctor.dashboard')->with('success', 'Appointment cancelled.');
    }


    private function fetchAppointmentRows(int $doctorId, string $scope, string $today)
    {
        $query = DB::table('appointments')
            ->join('users as patients', 'appointments.patient_id', '=', 'patients.id')
            ->leftJoin('medical_records', 'medical_records.appointment_id', '=', 'appointments.id')
            ->where('appointments.doctor_id', $doctorId)
            ->whereIn('appointments.status', ['confirmed', 'pending', 'completed'])
            ->select(
                'appointments.id',
                'appointments.uuid',
                'appointments.type',
                'appointments.start_time',
                'appointments.end_time',
                'appointments.appointment_date',
                'appointments.reason',
                'patients.name as patient_name',
                'medical_records.id as record_id',
                'medical_records.chief_complaint',
                'medical_records.diagnosis',
                'medical_records.notes',
                'medical_records.vitals'
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
            // Fetch all prescriptions for this record
            $prescriptions = [];
            $prescriptionSummary = null;
 
            if ($row->record_id) {
                $rxRows = DB::table('prescriptions')
                    ->where('medical_record_id', $row->record_id)
                    ->orderBy('issued_at')
                    ->get(['drug_name', 'dosage', 'frequency', 'duration', 'instructions', 'valid_until']);
 
                $prescriptions = $rxRows->map(fn($rx) => [
                    'drug_name'    => $rx->drug_name,
                    'dosage'       => $rx->dosage,
                    'frequency'    => $rx->frequency,
                    'duration'     => $rx->duration,
                    'instructions' => $rx->instructions,
                    'valid_until'  => $rx->valid_until,
                ])->toArray();
 
                $prescriptionSummary = $rxRows->isNotEmpty()
                    ? $rxRows->map(fn($rx) => $rx->drug_name . ' ' . $rx->dosage)->implode(', ')
                    : null;
            }
 
            return (object) [
                'uuid'                 => $row->uuid,
                'patient_name'         => $row->patient_name,
                'type_label'           => $row->type === 'telemedicine' ? 'Telemedicine' : 'In Person',
                'start_time'           => Carbon::parse($row->start_time)->format('g:i A'),
                'end_time'             => Carbon::parse($row->end_time)->format('g:i A'),
                'reason'               => $row->reason,
                'chief_complaint'      => $row->chief_complaint,
                'diagnosis'            => $row->diagnosis,
                'notes'                => $row->notes,
                'vitals'               => $row->vitals ?? '{}',
                'prescriptions'        => json_encode($prescriptions),
                'prescription_summary' => $prescriptionSummary,
            ];
        });
    }
}
