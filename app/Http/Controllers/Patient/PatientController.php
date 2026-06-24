<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
class PatientController extends Controller
{
    //
    public function home()
    {
        $patientId = Session::get('user_id');

        $patient = DB::table('users')->where('id', $patientId)->first();

        if (!$patient) {
            Session::flush();
            return redirect()->route('login');
        }

        // --- 1. Fetch Appointments ---
        $appointmentsData = DB::table('appointments')
            ->join('users', 'appointments.doctor_id', '=', 'users.id')
            ->where('appointments.patient_id', $patientId)
            ->where('appointments.status', '!=', 'cancelled')
            ->select(
                'users.name as doctor_name',
                'appointments.type',
                'appointments.appointment_date',
                'appointments.start_time',
                'appointments.end_time'
            )
            ->orderBy('appointments.appointment_date', 'asc')
            ->orderBy('appointments.start_time', 'asc')
            ->limit(5)
            ->get();

        $appointments = $appointmentsData->map(function ($app) {
            $fullStartStr = $app->appointment_date . ' ' . $app->start_time;
            $fullEndStr   = $app->appointment_date . ' ' . $app->end_time;

            return [
                'doctor_name' => 'Dr. ' . $app->doctor_name,
                'type_label'  => $app->type === 'in_person' ? 'In-Person' : 'Telemedicine',
                'start_time'  => date('M d, Y g:i A', strtotime($fullStartStr)),
                'end_time'    => date('g:i A', strtotime($fullEndStr)),
            ];
        })->toArray();

        // --- 2. Fetch Messages ---
        $messagesData = DB::table('messages')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->join('users', 'messages.sender_id', '=', 'users.id')
            ->where(function($query) use ($patientId){
                $query->where('conversations.participant_a', $patientId)
                      ->orWhere('conversations.participant_b', $patientId);
            })
            ->select('users.name as sender_name', 'messages.body', 'messages.created_at')
            ->orderBy('messages.created_at', 'desc')
            ->limit(5)
            ->get();

        $messages = $messagesData->map(function($msg){
            return [
                'sender_name' => 'Dr. ' . $msg->sender_name,
                'body'        => strlen($msg->body) > 40 ? substr($msg->body, 0, 40) . '...' : $msg->body,
            ];
        })->toArray();

        // --- 3. Build the Dynamic Calendar ---
        $now = \Carbon\Carbon::now();
        $startOfCalendar = $now->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::MONDAY);
        $endOfCalendar   = $now->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);
        
        $calendarWeeks = [];
        $currentDay = $startOfCalendar->copy();

        while ($currentDay <= $endOfCalendar) {
            $weekDays = [];
            $weekNumber = $currentDay->weekOfYear;

            for ($i = 0; $i < 7; $i++) {
                $weekDays[] = [
                    'date'     => $currentDay->copy(),
                    'in_month' => $currentDay->month === $now->month,
                    'is_today' => $currentDay->isToday(),
                ];
                $currentDay->addDay();
            }

            $calendarWeeks[] = [
                'number' => $weekNumber,
                'days'   => $weekDays,
            ];
        }

        $calendar = [
            'label' => $now->format('F Y'), // e.g., "June 2026"
            'weeks' => $calendarWeeks,
        ];

        // --- 4. Return everything to the view ---
        return view('patient.home', [
            'userName'     => $patient->name,
            'notification' => 'You have ' . count($appointments) . ' upcoming appointments.',
            'appointments' => $appointments,
            'messages'     => $messages,
            'calendar'     => $calendar,
        ]);
    }

    public function chat(){
        return view('patient.chat');
    }

    public function patientOnboarding(){
        return view('patient.onboarding');
    }
    
    public function patientOnboardingSubmit(Request $request)
    {
        $userId = Session::get('user_id');

        $validated = $request->validate([
            'phone'                   => 'required|string|max:20',
            'gender'                  => 'required|in:male,female,other',
            'avatar_url'              => 'nullable|url|max:500',
            
            'date_of_birth'           => 'required|date|before:today',
            'blood_type'              => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'height_cm'               => 'nullable|numeric|min:0',
            'weight_kg'               => 'nullable|numeric|min:0',
            
            'allergies'               => 'nullable|array',
            'allergies.*'             => 'string|max:255',
            'chronic_conditions'      => 'nullable|array',
            'chronic_conditions.*'    => 'string|max:255',
            
            'emergency_contact_name'  => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        try {
            DB::transaction(function () use ($userId, $validated) {
                
                DB::table('users')->where('id', $userId)->update([
                    'phone'      => $validated['phone'],
                    'gender'     => $validated['gender'],
                    'avatar_url' => $validated['avatar_url'] ?? null,
                ]);

                $profileId = DB::table('patient_profiles')->insertGetId([
                    'user_id'                 => $userId,
                    'date_of_birth'           => $validated['date_of_birth'],
                    'blood_type'              => $validated['blood_type'] ?? null,
                    'height_cm'               => $validated['height_cm'] ?? null,
                    'weight_kg'               => $validated['weight_kg'] ?? null,
                    
                    'allergies'               => !empty($validated['allergies']) ? json_encode($validated['allergies']) : null,
                    'chronic_conditions'      => !empty($validated['chronic_conditions']) ? json_encode($validated['chronic_conditions']) : null,
                    
                    'emergency_contact_name'  => $validated['emergency_contact_name'] ?? null,
                    'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                ]);

                Session::put('profile_id', $profileId);
            });

            return redirect()->route('patient.home')->with('success', 'Your medical profile has been successfully set up.');

        } catch (\Exception $e) {
            return redirect()->back()
                             ->withErrors(['error' => 'Failed to save profile. Please try again.'])
                             ->withInput();
        }
    }


    private function generateCalendarMatrix(){
        $currentMonth = date('m');
        $firstDayStr  = date('Y-m-01');

        $dayOfWeek = (int)date('N', strtotime($firstDayStr));
        $currentTimestamp = strtotime($firstDayStr . ' -' . ($dayOfWeek - 1) . ' days');

        $weeks = [];

        for ($w = 1; $w <= 6; $w++){
            $days = [];

            for($d = 0; $d < 7; $d++){
                $days[] =[
                    'date'      => (object)['day' => (int)date('j', $currentTimestamp)],
                    'in_month'  => date('m', $currentTimestamp) === $currentMonth,
                    'is_today'  => date('Y-m-d', $currentTimestamp) === date('Y-m-d'),
                ];
                $currentTimestamp += 86400;
            }

            $weeks[]=[
                'number'    => $w,
                'days'      => $days
            ];

            if(date('m', $currentTimestamp) !== $currentMonth){
                break;
            }
        }

        return[
            'label' => date('F Y'),
            'weeks' => $weeks,
        ];
    }
}