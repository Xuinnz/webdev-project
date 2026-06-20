<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controller\AuthController;
use App\Http\Controller\ProfileController;
use App\Http\Controller\AppointmentController;
use App\Http\Controller\ChatController;
use App\Http\Controller\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controller\Doctor\DoctorController as DoctorDoctorController;
use App\Http\Controller\Doctor\PatientController as DoctorPatientController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function (){
    return view('test');
});

//SHARED FUNCTIONS
Route::group(['prefix' => 'auth'], function(){
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
});


//SHARED FUNCTIONS
Route::group(['prefix' => 'appointments'], function() {
    Route::get('/{uuid}', [AppointmentController::class, 'getAppointment'])->name('appointment.get');

    Route::patch('/{uuid}', [AppointmentController::class, 'editAppointment'])->name('appointment.edit');
});

//SHARED FUNCTIONS
Route::group(['prefix' => 'conversations'], function() {
    Route::get('/', [ChatController::class, 'getConversations'])->name('convo.all');

    Route::post('/', [ChatController::class, 'startConversation'])->name('convo.start');

    Route::get('/{uuid}', [ChatController::class, 'getConversation'])->name('convo.get');

    Route::get('/{uuid}/messages', [ChatController::class, 'getMessages'])->name('convo.message');

    Route::post('/{uuid}/messages', [ChatController::class, 'sendMessages'])->name('convo.message.send');
});

//PATIENT SIDE FUNCTIONS
Route::middleware(['checkauth:patient'])->prefix('patient')->name('patient.')->group(function (){

});

//DOCTOR SIDE FUNCTIONS


Route::middleware(['checkauth:doctor'])->prefix('doctor')->name('doctor.')->group(function (){
    // ON BOARDING
    Route::get('/onboarding', [DoctorDoctorController::class, 'showOnBoarding'])->name('onboarding'); 
    Route::post('/onboarding', [DoctorDoctorController::class, 'processOnBoarding'])->name('onboarding.store');

    //SPECIALTY 
    Route::post('/specialty', [DoctorDoctorController::class, 'addSpecialty'])->name('specialty.store');
    
    //PROFILE
    Route::get('/profile', [DoctorDoctorController::class, 'getProfile'])->name('profile');
    Route::post('/profile', [DoctorDoctorController::class, 'updateProfile'])->name('profile.update');
    
    //SCHEDULE
    Route::get('/schedule', [DoctorDoctorController::class, 'getSchedule'])->name('schedule');
    Route::post('/schedule', [DoctorDoctorController::class, 'editSchedule'])->name('schedule.update');

    //APPOINTMENTS
    Route::get('/appointments', [DoctorAppointmentController::class, 'getAppointments'])->name('appointments.index');

    //PATIENTS
    Route::get('/patients', [DoctorPatientController::class, 'getPatients'])->name('patients.index');
    Route::get('/patients/{uuid}', [DoctorPatientController::class, 'getPatient'])->name('patients.show');

    //PATIENT-RECORDS
    Route::get('/patients/{uuid}/records', [DoctorPatientController::class, 'getPatientRecords'])->name('patient.records.index');
    Route::post('/patients/{uuid}/records', [DoctorPatientController::class, 'createPatientRecord'])->name('patient.records.store');
    Route::get('/records/{uuid}', [DoctorPatientController::class, 'getPatientRecord'])->name('records.show');

    //PATIENT-PRESCRIPTION
    Route::get('/patients/{uuid}/prescriptions', [DoctorPatientController::class, 'getPatientPrescriptions'])->name('patient.prescriptions.index');

});





