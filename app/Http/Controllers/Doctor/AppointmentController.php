<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AppointmentController extends Controller
{   
    //get specific doctor appointments
    public function getAppointments()
    {
        $userId = Session::get('user_id');

        $appointments = DB::table('appointments')
            ->where('appointments.doctor_id', $userId)
            // join to user table to get name
            ->join('users as patients', 'appointments.patient_id', '=', 'patients.id')
            ->select(
                'appointments.*', 
                'patients.name as patient_name', 
                'patients.phone as patient_phone',
                'patients.gender as patient_gender',
                'patients.avatar_url as patient_avatar' // Nice to have for the UI!
            )
            ->orderBy('appointments.appointment_date', 'asc')
            ->orderBy('appointments.start_time', 'asc')
            ->get();

        return view('doctor.appointments', compact('appointments'));
    }
}
