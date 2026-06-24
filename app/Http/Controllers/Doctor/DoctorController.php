<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DoctorController extends Controller
{
    private const CALENDAR_START_HOUR = 9;
    private const CALENDAR_END_HOUR = 18;
    private const CALENDAR_TOTAL_MINUTES = 540;

    public function dashboard()
    {
        $userId = Session::get('user_id');
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6);

        $weekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $weekDays[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('j') . ' ' . $date->format('D'),
                'is_today' => $date->isSameDay($today),
                'column' => $i + 1,
            ];
        }

        $rawAppointments = DB::table('appointments')
            ->join('users as patients', 'appointments.patient_id', '=', 'patients.id')
            ->leftJoin('patient_profiles', 'patients.id', '=', 'patient_profiles.user_id')
            ->where('appointments.doctor_id', $userId)
            ->whereBetween('appointments.appointment_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->whereIn('appointments.status', ['confirmed', 'pending', 'completed'])
            ->select(
                'appointments.*',
                'patients.name as patient_name',
                'patients.email as patient_email',
                'patients.phone as patient_phone',
                'patients.gender as patient_gender',
                'patient_profiles.date_of_birth as patient_dob',
                'patient_profiles.blood_type as patient_blood_type',
                'patient_profiles.height_cm as patient_height',
                'patient_profiles.weight_kg as patient_weight',
                'patient_profiles.allergies as patient_allergies',
                'patient_profiles.chronic_conditions as patient_chronic_conditions',
                'patient_profiles.emergency_contact_name as patient_emergency_contact_name',
                'patient_profiles.emergency_contact_phone as patient_emergency_contact_phone'
            )
            ->orderBy('appointments.appointment_date')
            ->orderBy('appointments.start_time')
            ->get();

        $dateToColumn = collect($weekDays)->pluck('column', 'date');

        $now = Carbon::now();

        $calendarAppointments = $rawAppointments->map(function ($appointment) use ($dateToColumn, $now) {
            $start = Carbon::parse($appointment->appointment_date . ' ' . $appointment->start_time);
            $end = Carbon::parse($appointment->appointment_date . ' ' . $appointment->end_time);
            $gridStart = $start->copy()->setTime(self::CALENDAR_START_HOUR, 0);
            $gridEnd = $start->copy()->setTime(self::CALENDAR_END_HOUR, 0);

            $startMinutes = max(0, min(self::CALENDAR_TOTAL_MINUTES, $gridStart->diffInMinutes($start)));
            $endMinutes = max($startMinutes + 15, min(self::CALENDAR_TOTAL_MINUTES, $gridStart->diffInMinutes($end)));
            $duration = $endMinutes - $startMinutes;

            return (object) [
                'uuid' => $appointment->uuid,
                'patient_name' => $appointment->patient_name,
                'patient_email' => $appointment->patient_email,
                'patient_phone' => $appointment->patient_phone ?? '—',
                'patient_gender' => $appointment->patient_gender ? ucfirst($appointment->patient_gender) : '—',
                'patient_dob' => $appointment->patient_dob ?? '—',
                'patient_blood_type' => $appointment->patient_blood_type ?? '—',
                'patient_height' => $appointment->patient_height ? $appointment->patient_height . ' cm' : '—',
                'patient_weight' => $appointment->patient_weight ? $appointment->patient_weight . ' kg' : '—',
                'patient_allergies' => is_string($appointment->patient_allergies) ? json_decode($appointment->patient_allergies) : ($appointment->patient_allergies ?? []),
                'patient_chronic_conditions' => is_string($appointment->patient_chronic_conditions) ? json_decode($appointment->patient_chronic_conditions) : ($appointment->patient_chronic_conditions ?? []),
                'patient_emergency_contact_name' => $appointment->patient_emergency_contact_name ?? '—',
                'patient_emergency_contact_phone' => $appointment->patient_emergency_contact_phone ?? '—',
                'type' => $appointment->type,
                'type_label' => $appointment->type === 'telemedicine' ? 'Telemedicine' : 'In-Person',
                'time_label' => $start->format('g:i A') . ' - ' . $end->format('g:i A'),
                'grid_column' => $dateToColumn[$appointment->appointment_date] ?? 1,
                'top_percent' => ($startMinutes / self::CALENDAR_TOTAL_MINUTES) * 100,
                'height_percent' => ($duration / self::CALENDAR_TOTAL_MINUTES) * 100,
                'is_past' => $end->lessThanOrEqualTo($now) || in_array($appointment->status, ['completed', 'no_show', 'cancelled']),
            ];
        });

        $calendarHours = [];
        for ($hour = self::CALENDAR_START_HOUR; $hour <= self::CALENDAR_END_HOUR; $hour++) {
            $calendarHours[] = sprintf('%02d:00', $hour);
        }

        return view('doctor.dashboard', compact('weekDays', 'calendarAppointments', 'calendarHours'));
    }

    public function showOnBoarding()
    {
        $specialties = DB::table('specialties')->get();

        return view('doctor.onboarding', compact('specialties'));
    }

    public function processOnBoarding(Request $request)
    {
        $userId = Session::get('user_id');
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female,other',
            'avatar_url' => 'nullable|url|max:500',
            'specialty_id' => 'required|exists:specialties,id',
            'license_number' => 'required|string|max:100',
            'bio' => 'nullable|string',
            'consultation_fee' => 'required|numeric|min:0',
            'schedules' => 'required|array|min:1',
            'schedules.*.weekday' => 'required|integer|between:0,6',
            'schedules.*.ranges' => 'required|array|min:1',
            'schedules.*.ranges.*.start' => 'required|date_format:H:i',
            'schedules.*.ranges.*.end' => 'required|date_format:H:i|after:schedules.*.ranges.*.start',
        ]);

        try {
            DB::transaction(function () use ($userId, $validated) {
                DB::table('users')->where('id', $userId)->update([
                    'phone' => $validated['phone'],
                    'gender' => $validated['gender'],
                    'avatar_url' => $validated['avatar_url'] ?? null,
                ]);

                $profileId = DB::table('doctor_profiles')->insertGetId([
                    'user_id' => $userId,
                    'specialty_id' => $validated['specialty_id'],
                    'license_number' => $validated['license_number'],
                    'bio' => $validated['bio'] ?? null,
                    'consultation_fee' => $validated['consultation_fee'],
                ]);

                $scheduleInserts = [];
                foreach ($validated['schedules'] as $schedule) {
                    $scheduleInserts[] = [
                        'doctor_id' => $userId,
                        'weekday' => $schedule['weekday'],
                        'slot_mask' => $this->calculateSlotMask($schedule['ranges']),
                    ];
                }

                DB::table('doctor_schedules')->insert($scheduleInserts);
                Session::put('profile_id', $profileId);
            });

            return redirect()->route('doctor.dashboard')->with('success', 'Welcome! Your profile is complete.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to save onboarding details. Please try again.'])->withInput();
        }
    }

    public function addSpecialty(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:specialties,name',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            DB::table('specialties')->insert([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            return redirect()->back()->with('success', $request->name . ' has been successfully added to the specialties list!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to add specialty. It might already exist.']);
        }
    }

    public function getProfile()
    {
        $userId = Session::get('user_id');
        $profile = DB::table('users')
            ->where('users.id', $userId)
            ->leftJoin('doctor_profiles', 'users.id', '=', 'doctor_profiles.user_id')
            ->leftJoin('specialties', 'doctor_profiles.specialty_id', '=', 'specialties.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'users.gender',
                'users.avatar_url',
                'doctor_profiles.license_number',
                'doctor_profiles.bio',
                'doctor_profiles.consultation_fee',
                'doctor_profiles.specialty_id',
                'specialties.name as specialty_name'
            )
            ->first();

        $specialties = DB::table('specialties')->orderBy('name')->get();

        return view('doctor.profile', compact('profile', 'specialties'));
    }

    public function updateProfile(Request $request)
    {
        $userId = Session::get('user_id');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female,other',
            'avatar_url' => 'nullable|url|max:500',
            'specialty_id' => 'required|exists:specialties,id',
            'bio' => 'nullable|string',
            'consultation_fee' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($userId, $validated) {
                DB::table('users')->where('id', $userId)->update([
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                    'gender' => $validated['gender'],
                    'avatar_url' => $validated['avatar_url'] ?? null,
                ]);
                DB::table('doctor_profiles')->where('user_id', $userId)->update([
                    'specialty_id' => $validated['specialty_id'],
                    'bio' => $validated['bio'] ?? null,
                    'consultation_fee' => $validated['consultation_fee'],
                ]);
            });

            return redirect()->back()->with('success', 'Your profile has been successfully updated.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update profile. Please try again.'])
                ->withInput();
        }
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
