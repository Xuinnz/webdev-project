<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DoctorController extends Controller
{   
    //name, email, password, role is done already done in register
    //phone, gender, avatar url
    //specialty_id, license number, bio, consultation fee, 
    //doctor schedule as wellm weekday, slotmask
    public function onBoarding(Request $request){
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
            
            // doctor schedule table (needs to be array of schedule)
            'schedules' => 'required|array|min:1',
            'schedules.*.weekday' => 'required|integer|between:0,6',
            'schedules.*.slot_mask' => 'required|integer|min:0'
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
                foreach ($request->schedules as $schedule) {
                    $scheduleInserts[] = [
                        'doctor_id' => $userId,
                        'weekday' => $schedule->weekday,
                        'slot_mask' => $schedule->slot_mask,
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
}
