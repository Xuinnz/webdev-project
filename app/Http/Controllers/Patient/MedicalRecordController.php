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

        $recordsData = DB::table('medical_records')
            ->join('users', 'medical_records.doctor_id', '=', 'users.id')
            ->where('medical_records.patient_id', $patientId)
            ->select(
                'medical_records.id',
                'medical_records.record_date',
                'medical_records.chief_complaint',
                'medical_records.diagnosis',
                'medical_records.vitals',
                'users.name as doctor_name'
            )
            ->orderBy('medical_records.record_date', 'desc')
            ->get();

        $prescriptionsData = DB::table('prescriptions')
            ->where('patient_id', $patientId)
            ->select('uuid', 'medical_record_id', 'drug_name', 'dosage', 'frequency')
            ->get();

        $prescriptionsGrouped = [];
        foreach ($prescriptionsData as $rx) {
            $prescriptionsGrouped[$rx->medical_record_id][] = [
                'uuid'      => $rx->uuid,
                'summary'   => $rx->drug_name . ' (' . $rx->dosage . ') - ' . $rx->frequency,
            ];
        }

        $medical_records = $recordsData->map(function ($record) use ($prescriptionsGrouped){
            return [
                'id'                => $record->id,
                'record_date'       => date('M d, Y', strtotime($record->record_date)),
                'chief_complaint'   => $record->chief_complaint ?? 'None recorded',
                'diagnosis'         => $record->diagnosis ?? 'Pending diagnosis',
                'doctor_name'       => 'Dr. ' . $record->doctor_name,
                
                'vitals'            => $record->vitals ? json_decode($record->vitals, true) : null,
                
                'prescriptions'     => $prescriptionsGrouped[$record->id] ?? [],
            ];
        })->toArray();

        return view('patient.medical-records', compact('medical_records'));
    }
}