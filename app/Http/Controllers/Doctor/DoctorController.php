<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DoctorController extends Controller
{   
    //show onboarding view
    public function showOnBoarding(){
        $specialties = DB::table('specialties')->get();

        return view('doctor.onboarding', compact('specialties'));
    }
        /* 
        Doctor schedule should look like this
        "schedules": [
        {
            "weekday": 1, 
            "ranges": [
                { "start": "08:00", "end": "11:00" },
                { "start": "13:00", "end": "17:00" }
            ]
        }
        ]
        */
    public function processOnBoarding(Request $request){
        $userId = Session::get('user_id');
        $request->validate([
            // users table
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female,other',
            'avatar_url' => 'nullable|url|max:500', 
            
            // doctor profile table
            'specialty_id' => 'required|exists:specialties,id',
            'license_number' => 'required|string|max:100',
            'bio' => 'nullable|string',
            'consultation_fee' => 'required|numeric|min:0',
            
            // doctor schedule
            'schedules' => 'required|array|min:1',
            'schedules.*.weekday' => 'required|integer|between:0,6',
            'schedules.*.ranges' => 'required|array|min:1',
            'schedules.*.ranges.*.start' => 'required|date_format:H:i',
            'schedules.*.ranges.*.end' => 'required|date_format:H:i|after:schedules.*.ranges.*.start',
        ]);

        try {
            //transaction for all pass or all fails
            DB::transaction(function () use ($userId, $request) {

                DB::table('users')->where('id', $userId)->update([
                    'phone' => $request->phone,
                    'gender' => $request->gender,
                    'avatar_url' => $request->avatar_url,
                ]);

                $profileId = DB::table('doctor_profiles')->insertGetId([
                    'user_id' => $userId,
                    'specialty_id' => $request->specialty_id,
                    'license_number' => $request->license_number,
                    'bio' => $request->bio,
                    'consultation_fee' => $request->consultation_fee,
                ]);
                //put it in an array
                $scheduleInserts = [];
                foreach ($validated['schedules'] as $schedule) {
                    // call helper function to turn time into int
                    $calculatedMask = $this->calculateSlotMask($schedule['ranges']);

                    $scheduleInserts[] = [
                        'doctor_id' => $userId,
                        'weekday' => $schedule['weekday'],
                        'slot_mask' => $calculatedMask, 
                    ];
                }
                
                // batch insert
                DB::table('doctor_schedules')->insert($scheduleInserts);
                Session::put('profile_id', $profileId);
            });

            return redirect()->route('doctor.dashboard')->with('success', 'Welcome! Your profile is complete.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to save onboarding details. Please try again.'])->withInput();
        }
    }

    //add general doctor specialties 
    public function addSpecialty(Request $request){
        $request->validate([
            'name' => 'required|string|max:100|unique:specialties,name',
            'description' => 'nullable|string|max:255',
        ]);
        try {
            DB::table('specialties')->insert([
                'name' => $request->name,
                'description' => $request->description
            ]);
            return redirect()->back()->with('success', $request->name . ' has been successfully added to the specialties list!');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to add specialty. It might already exist.']);
        }
    }

    //get session doctor profile
    public function getProfile(){
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
                'specialties.name as specialty_name'
            )
            ->first();
        
        return view('doctor.profile', compact('profile'));
    }
    
    //update session doctor profile
    public function updateProfile(Request $request)
    {
        $userId = Session::get('user_id');
        $request->validate([
            // users table
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female,other',
            'avatar_url' => 'nullable|url|max:500', 
            
            // doctor profiles table
            'specialty_id' => 'required|exists:specialties,id',
            'bio' => 'nullable|string',
            'consultation_fee' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($userId, $validated) {
                DB::table('users')->where('id', $userId)->update([
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'gender' => $request->gender,
                    'avatar_url' => $request->avatar_url,
                ]);
                DB::table('doctor_profiles')->where('user_id', $userId)->update([
                    'specialty_id' => $request->specialty_id,
                    'bio' => $request->bio,
                    'consultation_fee' => $request->consultation_fee,
                ]);
            });
            return redirect()->back()->with('success', 'Your profile has been successfully updated.');

        } catch (\Exception $e) {
            return redirect()->back()
                             ->withErrors(['error' => 'Failed to update profile. Please try again.'])
                             ->withInput();
        }
    }

    //get session doctor template sched
    public function getSchedule(){
        $userId = Session::get('user_id');
        $schedules = DB::table('doctor_schedules')->where('doctor_id', $userId)->get();

        $formattedSchedules = [];
        foreach ($schedules as $schedule) {
            $formattedSchedules[] = [
                'weekday' => $schedule->weekday,
                'ranges' => $this->decodeSlotMask((int) $schedule->slot_mask) 
            ];
        }
        return view('doctors.schedule', ['schedules' => $formattedSchedules]);
    }

    //update session doctor template 
    public function editSchedule(Request $request)
    {
        $userId = Session::get('user_id');
        $request->validate([
            'schedules' => 'required|array|min:1',
            'schedules.*.weekday' => 'required|integer|between:0,6',
            'schedules.*.ranges' => 'required|array|min:1',
            'schedules.*.ranges.*.start' => 'required|date_format:H:i',
            'schedules.*.ranges.*.end' => 'required|date_format:H:i|after:schedules.*.ranges.*.start',
        ]);
        //instead of updating, we delete the whole table, and create a new one.
        try {
            DB::transaction(function () use ($userId, $request) {
                DB::table('doctor_schedules')->where('doctor_id', $userId)->delete();
                $scheduleInserts = [];
                foreach ($validated['schedules'] as $schedule) {
                    $calculatedMask = $this->calculateSlotMask($schedule['ranges']);

                    $scheduleInserts[] = [
                        'doctor_id' => $userId,
                        'weekday' => $schedule['weekday'],
                        'slot_mask' => $calculatedMask,
                    ];
                }
                DB::table('doctor_schedules')->insert($scheduleInserts);
            });

            return redirect()->back()->with('success', 'Your weekly schedule has been successfully updated!');

        } catch (\Exception $e) {
            return redirect()->back()
                             ->withErrors(['error' => 'Failed to update schedule. Please try again.']);
        }
    }

    //helper function to transform array to int
    private function calculateSlotMask(array $timeRanges): int{
        $baseTime = \Carbon\Carbon::createFromTimeString('08:00:00');
        $dailyMask = 0; // Start with 0 (Fully booked/Off-duty)

        foreach ($timeRanges as $range) {
            $startTime = \Carbon\Carbon::createFromTimeString($range['start']);
            $endTime = \Carbon\Carbon::createFromTimeString($range['end']);

            // calculate the start time bit position
            $startBit = $baseTime->diffInMinutes($startTime) / 15;
            
            // calculate how many bits is the duration
            $durationBits = $startTime->diffInMinutes($endTime) / 15;

            // create a mask for this
            $chunkMask = ((1 << $durationBits) - 1) << $startBit;

            // use bitwise or to combine everything
            $dailyMask = $dailyMask | $chunkMask;
        }

        return $dailyMask;
    }

    //helper function to transform int to arr
    //basically, we transform sequence of 1 bits into readable time ranges
    private function decodeSlotMask(int $mask): array{
        $ranges = [];
        $inRange = false;
        $rangeStartBit = 0;
        for ($i = 0; $i <= 36; $i++) {
            
            // check if ith position is 1
            $isSet = ($mask & (1 << $i)) !== 0;

            if ($isSet && !$inRange) {
                //if we found a 1, time start
                $inRange = true;
                $rangeStartBit = $i;
            } elseif (!$isSet && $inRange) {
                //if we found a 0, stop counting since we got the time end
                $inRange = false;
                $rangeEndBit = $i;

                // Convert the bit positions back to real time
                $startTime = \Carbon\Carbon::parse('08:00:00')->addMinutes($rangeStartBit * 15)->format('H:i');
                $endTime = \Carbon\Carbon::parse('08:00:00')->addMinutes($rangeEndBit * 15)->format('H:i');

                $ranges[] = [
                    'start' => $startTime,
                    'end' => $endTime
                ];
            }
        }
        return $ranges;
    }
}
