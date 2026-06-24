<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DoctorController extends Controller
{
    private const CALENDAR_START_HOUR = 8;
    private const CALENDAR_END_HOUR = 17;
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
                'id' => $appointment->id,   
                'status' => $appointment->status,
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
        $specialties     = DB::table('specialties')->orderBy('name')->get();
        $defaultDuration = 30;
 
        return view('doctor.onboarding', [
            'specialties' => $specialties,
            'slots'       => $this->generateSlots($defaultDuration),
            'durations'   => $this->availableDurations(),
        ]);
    }


    public function processOnBoarding(Request $request)
    {
        $userId    = Session::get('user_id');
        $validated = $request->validate([
            'phone'                              => 'required|string|max:20',
            'gender'                             => 'required|in:male,female,other',
            'avatar_url'                         => 'nullable|url|max:500',
            'specialty_id'                       => 'required|exists:specialties,id',
            'license_number'                     => 'required|string|max:100',
            'bio'                                => 'nullable|string',
            'consultation_fee'                   => 'required|numeric|min:0',
            'slot_duration_minutes'              => 'required|integer|in:15,30,45,60,90,120',
            'schedules'                          => 'required|array|min:1',
            'schedules.*.weekday'                => 'required|integer|between:0,6',
            'schedules.*.ranges'                 => 'required|array|min:1',
            'schedules.*.ranges.*.start'         => 'required|date_format:H:i',
            'schedules.*.ranges.*.end'           => 'required|date_format:H:i|after:schedules.*.ranges.*.start',
        ]);
 
        try {
            DB::transaction(function () use ($userId, $validated) {
 
                DB::table('users')->where('id', $userId)->update([
                    'phone'      => $validated['phone'],
                    'gender'     => $validated['gender'],
                    'avatar_url' => $validated['avatar_url'] ?? null,
                ]);
 
                $profileId = DB::table('doctor_profiles')->insertGetId([
                    'user_id'                => $userId,
                    'specialty_id'           => $validated['specialty_id'],
                    'license_number'         => $validated['license_number'],
                    'bio'                    => $validated['bio'] ?? null,
                    'consultation_fee'       => $validated['consultation_fee'],
                    'slot_duration_minutes'  => $validated['slot_duration_minutes'],
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
 
                $scheduleInserts = [];
                foreach ($validated['schedules'] as $schedule) {
                    $scheduleInserts[] = [
                        'doctor_id'  => $userId,
                        'weekday'    => $schedule['weekday'],
                        'slot_mask'  => $this->calculateSlotMask($schedule['ranges']),
                    ];
                }
 
                DB::table('doctor_schedules')->insert($scheduleInserts);
                Session::put('profile_id', $profileId);
            });
 
            return redirect()
                ->route('doctor.dashboard')
                ->with('success', 'Welcome! Your profile is complete.');
 
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
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
                'doctor_profiles.slot_duration_minutes',
                'specialties.name as specialty_name'
            )
            ->first();
    
        $specialties     = DB::table('specialties')->orderBy('name')->get();
        $duration        = $profile->slot_duration_minutes ?? 30;
    
        // Fetch existing schedule rows for this doctor
        $scheduleRows = DB::table('doctor_schedules')
            ->where('doctor_id', $userId)
            ->get()
            ->keyBy('weekday');  // keyed by weekday int for easy lookup
    
        // Decode each day's slot_mask back into selectedStarts
        // so Alpine can pre-populate the grid exactly as it was saved
        $existingSchedule = [];
        foreach ($scheduleRows as $weekday => $row) {
            $existingSchedule[$weekday] = $this->decodeMaskToStarts((int) $row->slot_mask, $duration);
        }
    
        // Generate slots for the current duration to pass to the view
        $slots = $this->generateSlots($duration);
        $durations = $this->availableDurations();
    
        return view('doctor.profile', compact(
            'profile',
            'specialties',
            'slots',
            'duration',
            'existingSchedule',
            'durations'
        ));
    }
    
    // ──────────────────────────────────────────────────────────────
    //  updateProfile
    // ──────────────────────────────────────────────────────────────
    
    public function updateProfile(Request $request)
    {
        $userId    = Session::get('user_id');
        $validated = $request->validate([
            'name'                               => 'required|string|max:255',
            'phone'                              => 'required|string|max:20',
            'gender'                             => 'required|in:male,female,other',
            'avatar_url'                         => 'nullable|url|max:500',
            'specialty_id'                       => 'required|exists:specialties,id',
            'bio'                                => 'nullable|string',
            'consultation_fee'                   => 'required|numeric|min:0',
            'slot_duration_minutes'              => 'required|integer|in:15,30,45,60,90,120',
            'schedules'                          => 'required|array|min:1',
            'schedules.*.weekday'                => 'required|integer|between:0,6',
            'schedules.*.ranges'                 => 'required|array|min:1',
            'schedules.*.ranges.*.start'         => 'required|date_format:H:i',
            'schedules.*.ranges.*.end'           => 'required|date_format:H:i|after:schedules.*.ranges.*.start',
        ]);
    
        try {
            DB::transaction(function () use ($userId, $validated) {
    
                DB::table('users')->where('id', $userId)->update([
                    'name'       => $validated['name'],
                    'phone'      => $validated['phone'],
                    'gender'     => $validated['gender'],
                    'avatar_url' => $validated['avatar_url'] ?? null,
                    'updated_at' => now(),
                ]);
    
                DB::table('doctor_profiles')->where('user_id', $userId)->update([
                    'specialty_id'          => $validated['specialty_id'],
                    'bio'                   => $validated['bio'] ?? null,
                    'consultation_fee'      => $validated['consultation_fee'],
                    'slot_duration_minutes' => $validated['slot_duration_minutes'],
                    'updated_at'            => now(),
                ]);
    
                // Delete existing schedule rows and reinsert
                // Simpler than diffing — schedule changes are infrequent
                DB::table('doctor_schedules')->where('doctor_id', $userId)->delete();
    
                $scheduleInserts = [];
                foreach ($validated['schedules'] as $schedule) {
                    $scheduleInserts[] = [
                        'doctor_id' => $userId,
                        'weekday'   => $schedule['weekday'],
                        'slot_mask' => $this->calculateSlotMask($schedule['ranges']),
                    ];
                }
    
                DB::table('doctor_schedules')->insert($scheduleInserts);
            });
    
            return redirect()->back()->with('success', 'Profile updated successfully.');
    
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update profile. Please try again.'])
                ->withInput();
        }
    }
    
    // ──────────────────────────────────────────────────────────────
    //  PRIVATE: decode a slot_mask back to an array of start strings
    //  e.g. mask=3, duration=30 → ['08:00', '08:30'] (bits 0+1 set)
    //  Used to pre-populate the schedule grid on profile edit
    // ──────────────────────────────────────────────────────────────
    
    private function decodeMaskToStarts(int $mask, int $durationMinutes): array
    {
        $bitsPerSlot = $durationMinutes / 15;
        $starts      = [];
        $totalBits   = 36; // 08:00 → 17:00
    
        for ($bit = 0; $bit + $bitsPerSlot <= $totalBits; $bit += $bitsPerSlot) {
            // Build the mask for this slot
            $slotMask = ((1 << $bitsPerSlot) - 1) << $bit;
    
            // If all bits of this slot are set, the slot was selected
            if (($mask & $slotMask) === $slotMask) {
                $startMinutes = 8 * 60 + $bit * 15;
                $h = str_pad((int) floor($startMinutes / 60), 2, '0', STR_PAD_LEFT);
                $m = str_pad($startMinutes % 60, 2, '0', STR_PAD_LEFT);
                $starts[] = "{$h}:{$m}";
            }
        }
    
        return $starts;
    }

    private function generateSlots(int $durationMinutes): array
    {
        $slots   = [];
        $current = Carbon::createFromTimeString('08:00:00');
        $end     = Carbon::createFromTimeString('17:00:00');
 
        while ($current->copy()->addMinutes($durationMinutes)->lte($end)) {
            $slotEnd = $current->copy()->addMinutes($durationMinutes);
 
            $slots[] = [
                'start' => $current->format('H:i'),
                'end'   => $slotEnd->format('H:i'),
                'label' => $current->format('g:i A') . ' – ' . $slotEnd->format('g:i A'),
            ];
 
            $current->addMinutes($durationMinutes);
        }
 
        return $slots;
    }

    private function availableDurations(): array
    {
        return [
            ['value' => 15,  'label' => '15 min'],
            ['value' => 30,  'label' => '30 min'],
            ['value' => 45,  'label' => '45 min'],
            ['value' => 60,  'label' => '1 hr'],
            ['value' => 90,  'label' => '1 hr 30 min'],
            ['value' => 120, 'label' => '2 hrs'],
        ];
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
