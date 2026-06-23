<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
class PatientController extends Controller
{
    //
    public function home(){
        $patientId = Session::get('user_id');

        $patient = DB::table('users')
            ->where('id', $patientId)
            ->first();

        if (!$patient) {
            Session::flush();

            return redirect()->route('login');
        }

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

            return[
                'doctor_name' => 'Dr. ' . $app->doctor_name,
                'type_label' => $app->type === 'in_person' ? 'In-Person' : 'Telemedicine',
                'start_time' => date('M d, Y g:i A', strtotime($fullStartStr)),
                'end_time' => date('g:i A', strtotime($fullEndStr)),
            ];
        })->toArray();

        $messagesData = DB::table('messages')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->join('users', 'messages.sender_id', '=', 'users.id')
            ->where(function($query) use ($patientId){
                $query->where('conversations.participant_a', $patientId)
                    ->orWhere('conversations.participant_b', $patientId);
            })
            ->select('users.name as sender_name', 'messages.body')
            ->orderBy('messages.created_at', 'desc')
            ->limit(5)
            ->get();

            $messages = $messagesData->map(function($msg){
                return[
                    'sender_name'   => 'Dr. ' . $msg->sender_name,
                    'body'          => strlen($msg->body) > 40 ? substr($msg->body, 0, 40) . '...' : $msg->body,
                ];
            })->toArray();
    }

    public function chat(){
        return view('chat');
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