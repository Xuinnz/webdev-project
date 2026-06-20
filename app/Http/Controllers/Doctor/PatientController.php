<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    // get session doctor patients
    public function getPatients(){
        $doctorId = Session::get('user_id');

        $patients = DB::table('users')
            ->join('appointments', 'users.id', '=', 'appointments.patient_id')
            ->where('appointments.doctor_id', $doctorId)
            ->select(
                'users.uuid', 
                'users.name', 
                'users.email', 
                'users.phone', 
                'users.gender', 
                'users.avatar_url'
            )
            ->distinct() 
            ->orderBy('users.name', 'asc') 
            ->get();

        return view('doctor.patients.index', compact('patients'));
    }

    // get specific patient
    public function getPatient($uuid){
        $doctorId = Session::get('user_id');

        $patient = DB::table('users')
            ->join('appointments', 'users.id', '=', 'appointments.patient_id')
            ->leftJoin('patient_profiles', 'users.id', '=', 'patient_profiles.user_id')
            ->where('users.uuid', $uuid)
            ->where('appointments.doctor_id', $doctorId)
            ->select(
                // user data
                'users.uuid', 
                'users.name', 
                'users.email', 
                'users.phone', 
                'users.gender', 
                'users.avatar_url',
                'users.created_at',
                
                // detailed patient profile
                'patient_profiles.date_of_birth',
                'patient_profiles.blood_type',
                'patient_profiles.height_cm',
                'patient_profiles.weight_kg',
                'patient_profiles.allergies',
                'patient_profiles.chronic_conditions',
                'patient_profiles.emergency_contact_name',
                'patient_profiles.emergency_contact_phone'
            )
            ->first();

        if (!$patient) {
            abort(404, 'Patient not found or unauthorized access.');
        }

        // decode JSON to php array
        if ($patient->allergies) {
            $patient->allergies = json_decode($patient->allergies);
        }
        if ($patient->chronic_conditions) {
            $patient->chronic_conditions = json_decode($patient->chronic_conditions);
        }

        return view('doctor.patients.show', compact('patient'));
    }

    // get patient all record from session doctor
    public function getPatientRecords($uuid)
    {
        $doctorId = Session::get('user_id');

        $patient = DB::table('users')
            ->where('uuid', $uuid)
            ->where('role', 'patient')
            ->first();

        if (!$patient) {
            abort(404, 'Patient not found.');
        }

        // 2. Fetch the summary records
        $records = DB::table('medical_records')
            ->where('patient_id', $patient->id)
            ->where('doctor_id', $doctorId) 
            ->select(
                'uuid',
                'patient_id',
                'doctor_id',
                'appointment_id',
                'record_date',
                'chief_complaint',
                'diagnosis'
            )
            ->orderBy('record_date', 'desc')
            ->get();

        return view('doctor.records.index', compact('records', 'patient'));
    }

    // specific record
    public function getPatientRecord($uuid){
        $doctorId = Session::get('user_id');

        $record = DB::table('medical_records')
            ->where('uuid', $uuid)
            ->where('doctor_id', $doctorId)
            ->first();

        if (!$record) {
            abort(404, 'Medical record not found or unauthorized access.');
        }

        // decode JSON arrays to PHP arrays
        if ($record->vitals) {
            $record->vitals = json_decode($record->vitals);
        }
        if ($record->attachments) {
            $record->attachments = json_decode($record->attachments);
        }

        // fetch all prescriptions of the record
        $prescriptions = DB::table('prescriptions')
            ->where('medical_record_id', $record->id)
            ->orderBy('issued_at', 'desc')
            ->get();

        // attach prescription to record
        $record->prescriptions = $prescriptions;

        return view('doctor.records.show', compact('record'));
    }

    // create specific patient record
    public function createPatientRecord(Request $request, $uuid){
        $doctorId = Session::get('user_id');

        $patient = DB::table('users')
            ->where('uuid', $uuid)
            ->where('role', 'patient')
            ->first();

        if (!$patient) {
            abort(404, 'Patient not found.');
        }
        //we pass the value to validated so it will go thru even if not all are validated
        $validated = $request->validate([
            // medical records
            'appointment_id'  => 'nullable|exists:appointments,id',
            'record_date'     => 'required|date',
            'chief_complaint' => 'nullable|string',
            'diagnosis'       => 'nullable|string',
            'notes'           => 'nullable|string',
            
            // JSON Fields (Frontend sends them as arrays/objects)
            'vitals'          => 'nullable|array',
            'attachments'     => 'nullable|array',

            // Prescriptions 
            'prescriptions'                  => 'nullable|array',
            'prescriptions.*.drug_name'      => 'required_with:prescriptions|string|max:255',
            'prescriptions.*.dosage'         => 'required_with:prescriptions|string|max:100',
            'prescriptions.*.frequency'      => 'required_with:prescriptions|string|max:100',
            'prescriptions.*.duration'       => 'nullable|string|max:100',
            'prescriptions.*.instructions'   => 'nullable|string',
            'prescriptions.*.valid_until'    => 'nullable|date',
        ]);

        try {
            DB::transaction(function () use ($doctorId, $patient, $validated) {
                //medical record first. then we get it's ID for later
                $recordId = DB::table('medical_records')->insertGetId([
                    'patient_id'      => $patient->id,
                    'doctor_id'       => $doctorId,
                    'appointment_id'  => $validated['appointment_id'] ?? null,
                    'record_date'     => $validated['record_date'],
                    'chief_complaint' => $validated['chief_complaint'] ?? null,
                    'diagnosis'       => $validated['diagnosis'] ?? null,
                    'notes'           => $validated['notes'] ?? null,
                    // Convert the PHP arrays back into JSON strings for MySQL
                    'vitals'          => isset($validated['vitals']) ? json_encode($validated['vitals']) : null,
                    'attachments'     => isset($validated['attachments']) ? json_encode($validated['attachments']) : null,
                ]);

                // B. insert all prescriptions
                if (!empty($validated['prescriptions'])) {
                    $prescriptionInserts = [];
                    
                    foreach ($validated['prescriptions'] as $rx) {
                        $prescriptionInserts[] = [
                            'medical_record_id' => $recordId,
                            'patient_id'        => $patient->id, 
                            'doctor_id'         => $doctorId,    
                            'drug_name'         => $rx['drug_name'],
                            'dosage'            => $rx['dosage'],
                            'frequency'         => $rx['frequency'],
                            'duration'          => $rx['duration'] ?? null,
                            'instructions'      => $rx['instructions'] ?? null,
                            'valid_until'       => $rx['valid_until'] ?? null,
                            'status'            => 'active'
                        ];
                    }

                    DB::table('prescriptions')->insert($prescriptionInserts);
                }

                // auto complete the linked appointment
                if (!empty($validated['appointment_id'])) {
                    DB::table('appointments')
                        ->where('id', $validated['appointment_id'])
                        ->update(['status' => 'completed']);
                }
            });

            return redirect()->route('doctor.patient.records.index', $patient->uuid)
                             ->with('success', 'Medical record and prescriptions successfully saved.');

        } catch (\Exception $e) {
            return redirect()->back()
                             ->withErrors(['error' => 'Failed to save medical record. Please try again.'])
                             ->withInput();
        }
    }
}