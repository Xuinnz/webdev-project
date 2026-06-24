<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DoctorViewsTestSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $specialtyId = DB::table('specialties')->insertGetId([
            'name' => 'General Medicine',
            'description' => 'Primary care and general practice',
            'created_at' => now(),
        ]);

        $doctorId = DB::table('users')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Dr. Jane Smith',
            'email' => 'doctor@example.com',
            'password' => $password,
            'role' => 'doctor',
            'phone' => '555-0100',
            'gender' => 'female',
            'avatar_url' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctor_profiles')->insertGetId([
            'user_id' => $doctorId,
            'specialty_id' => $specialtyId,
            'license_number' => 'MD-12345',
            'bio' => 'Experienced general practitioner.',
            'consultation_fee' => 75.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctor_schedules')->insert([
            'doctor_id' => $doctorId,
            'weekday' => 1,
            'slot_mask' => 65535,
        ]);

        DB::table('users')->insert([
            'uuid' => (string) Str::uuid(),
            'name' => 'Dr. New User',
            'email' => 'doctor.new@example.com',
            'password' => $password,
            'role' => 'doctor',
            'phone' => null,
            'gender' => null,
            'avatar_url' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patientId = DB::table('users')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'John Patient',
            'email' => 'patient@example.com',
            'password' => $password,
            'role' => 'patient',
            'phone' => '555-0200',
            'gender' => 'male',
            'avatar_url' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('patient_profiles')->insert([
            'user_id' => $patientId,
            'date_of_birth' => '1990-05-15',
            'blood_type' => 'O+',
            'height_cm' => 175.00,
            'weight_kg' => 70.00,
            'allergies' => json_encode(['Penicillin']),
            'chronic_conditions' => json_encode(['Hypertension']),
            'emergency_contact_name' => 'Jane Patient',
            'emergency_contact_phone' => '555-0201',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $monday = $weekStart->toDateString();
        $wednesday = $weekStart->copy()->addDays(2)->toDateString();

        $todayAppointmentId = DB::table('appointments')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'appointment_date' => $today->toDateString(),
            'start_time' => '15:00:00',
            'end_time' => '17:00:00',
            'slot_mask' => 15,
            'status' => 'confirmed',
            'type' => 'in_person',
            'reason' => 'Headache',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('appointments')->insert([
            'uuid' => (string) Str::uuid(),
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'slot_mask' => 240,
            'status' => 'pending',
            'type' => 'in_person',
            'reason' => 'Follow-up',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('appointments')->insert([
            'uuid' => (string) Str::uuid(),
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'appointment_date' => $monday,
            'start_time' => '09:30:00',
            'end_time' => '11:20:00',
            'slot_mask' => 240,
            'status' => 'confirmed',
            'type' => 'in_person',
            'reason' => 'Weekly visit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('appointments')->insert([
            'uuid' => (string) Str::uuid(),
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'appointment_date' => $wednesday,
            'start_time' => '11:30:00',
            'end_time' => '12:30:00',
            'slot_mask' => 240,
            'status' => 'confirmed',
            'type' => 'telemedicine',
            'reason' => 'Tele follow-up',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $recordId = DB::table('medical_records')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'appointment_id' => $todayAppointmentId,
            'record_date' => $today->toDateString(),
            'chief_complaint' => 'Headache',
            'diagnosis' => 'Tension headache',
            'notes' => 'Stress',
            'vitals' => json_encode(['bp' => '120/80']),
            'attachments' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('prescriptions')->insert([
            'uuid' => (string) Str::uuid(),
            'medical_record_id' => $recordId,
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'drug_name' => 'Paracetamol',
            'dosage' => '500mg',
            'frequency' => 'As needed',
            'duration' => '7 days',
            'instructions' => 'Take with food',
            'valid_until' => $today->copy()->addDays(7)->toDateString(),
            'status' => 'active',
            'issued_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command?->info('Test data seeded. Login: doctor@example.com / password');
    }
}
