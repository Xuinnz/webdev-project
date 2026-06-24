<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Session::has('user_id')) {
            $role = Session::get('user_role');
            if ($role && Route::has($role . '.dashboard')) {
                return redirect()->route($role . '.dashboard');
            }
        }

        return view('login');
    }

    //register (for both patient and doctor)
    public function register(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:doctor,patient',
        ]);

        try {
            $userId = DB::table('users')->insertGetId([
                'uuid' => Str::uuid(),
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);
            Session::put('user_id', $userId);
            Session::put('user_role', $request->role);
            Session::put('user_name', $request->name);

            //redirect to specific role's onboarding
            return redirect()->route($request->role === 'doctor' ? 'doctor.onboarding' : 'patient.onboarding');

        } catch (\Exception $e){
            return back()->withErrors(['error' => 'Registration failed. Please try again.'])->withInput();
        }
    }

    public function login(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = DB::table('users')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        Session::put('user_id', $user->id);
        Session::put('user_role', $user->role);
        Session::put('user_name', $user->name);

        if ($user->role === 'doctor') {
            $profile = DB::table('doctor_profiles')->where('user_id', $user->id)->first();

            if ($profile) {
                Session::put('profile_id', $profile->id);
                return redirect()->route('doctor.dashboard');
            }

            return redirect()->route('doctor.onboarding');
        }

        if ($user->role === 'patient') {
            $profile = DB::table('patient_profiles')->where('user_id', $user->id)->first();

            if ($profile) {
                Session::put('profile_id', $profile->id);
                return redirect()->route('patient.dashboard');
            }

            return redirect()->route('patient.onboarding');
        }
    }

    public function logout(Request $request){
        Session::flush();
        return redirect()->route('login')->with('success', 'You have been logged out.');
    }

    // public function emailVerify(){
    // }

    // public function passwordReset(){
    // }
}
