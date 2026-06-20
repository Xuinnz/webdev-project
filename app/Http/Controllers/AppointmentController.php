<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AppointmentController extends Controller
{
    public function getAppointment($uuid){
        $userId = Session::get('user_id');
        $role = Session::get('user_role');
        $appointment = DB::table('appointments')
            ->where('uuid', $uuid)
            ->first();
        //verify if appointment exist
        if (!$appointment) {
            return redirect()->back()->withErrors(['error' => 'Appointment not found.']);
        }
        //verify if the session own this appointment
        if (($role === 'doctor' && $appointment->doctor_id !== $userId) || 
            ($role === 'patient' && $appointment->patient_id !== $userId)) {
            return redirect()->route($role . '.dashboard')->withErrors(['error' => 'Unauthorized access.']);
        }

        
        return view('appointments', compact('appoinment'));
    }

    
    //ONLY STATUS CAN BE UPDATED
    public function editAppointment(Request $request, $uuid){
        $userId = Session::get('user_id');
        $role = Session::get('user_role');
        $appointment = DB::table('appointments')
                ->where('uuid', $uuid)
                ->first();
        //verify if appointment exist
        if (!$appointment) {
            return redirect()->back()->withErrors(['error' => 'Appointment not found.']);
        }
        //verify if the session own this appointment
        if (($role === 'doctor' && $appointment->doctor_id !== $userId) || 
            ($role === 'patient' && $appointment->patient_id !== $userId)) {
            return redirect()->route($role . '.dashboard')->withErrors(['error' => 'Unauthorized access.']);
        }
        //validate
        $request->validate([
            'status' => 'required|in:confirmed,completed,cancelled,no_show'
        ]);
        try {
            //update status appointment
            DB::table('appointments')->where('id', $uuid)->update([
                'status' => $request['status']
            ]);
            return redirect()->route('appointments.show', $uuid)
                             ->with('success', 'Appointment status updated to ' . $request['status']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update appointment status.']);
        }
        
    }
}
