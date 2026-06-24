<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class MedicalRecordController extends Controller
{
    public function index()
    {
        $patientId = Session::get('user_id');

        // 1. Fetch Records (Added 'diagnosis' to the select statement)
        $recordsData = DB::table('medical_records')
            ->join('users', 'medical_records.doctor_id', '=', 'users.id')
            ->where('medical_records.patient_id', $patientId)
            ->select(
                'medical_records.id',
                'medical_records.record_date',
                'medical_records.chief_complaint',
                'medical_records.diagnosis', // FIXED: Added this line!
                'medical_records.vitals',
                'users.name as doctor_name'
            )
            ->orderBy('medical_records.record_date', 'desc')
            ->get();

        // 2. Fetch Prescriptions
        $prescriptionsData = DB::table('prescriptions')
            ->where('patient_id', $patientId)
            ->select('uuid', 'medical_record_id', 'drug_name', 'dosage', 'frequency')
            ->get();

        // 3. Group Prescriptions by Record ID
        $prescriptionsGrouped = [];
        foreach ($prescriptionsData as $rx) {
            $prescriptionsGrouped[$rx->medical_record_id][] = [
                'uuid'      => $rx->uuid,
                'summary'   => $rx->drug_name . ' (' . $rx->dosage . ') - ' . $rx->frequency,
            ];
        }

        // 4. Map the Final Output
        $medical_records = $recordsData->map(function ($record) use ($prescriptionsGrouped){
            return [
                'id'                => $record->id,
                'record_date'       => date('M d, Y', strtotime($record->record_date)),
                'chief_complaint'   => $record->chief_complaint ?? 'None recorded',
                'diagnosis'         => $record->diagnosis ?? 'Pending diagnosis',
                'doctor_name'       => 'Dr. ' . $record->doctor_name,
                
                // Decode the JSON vitals string back into a usable array for Alpine
                'vitals'            => $record->vitals ? json_decode($record->vitals, true) : null,
                
                // Attach the grouped prescriptions (or an empty array if none exist)
                'prescriptions'     => $prescriptionsGrouped[$record->id] ?? [],
            ];
        })->toArray();

        return view('patient.medical-records', compact('medical_records'));
    }
}