<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Patient\AppointmentController as PatientAppointmentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Patient\MedicalRecordController;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Doctor\DoctorController as DoctorDoctorController;
use App\Http\Controllers\Doctor\PatientController as DoctorPatientController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function (){
    return view('test');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');

//SHARED FUNCTIONS
Route::group(['prefix' => 'auth'], function(){
    Route::get('/register', [AuthController::class, 'registerForm'])->name('auth.register.show');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

    Route::get('login', [AuthController::class, 'showLoginForm'])->name('auth.login.show');
    
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
    //onboarding
    Route::get('/onboarding', [PatientController::class, 'patientOnboarding'])->name('onboarding');
    Route::post('/onboarding', [PatientController::class, 'patientOnboardingSubmit'])->name('onboarding.store');

    //dashboard
    Route::get('/dashboard', [PatientController::class, 'dashboard'])->name('dashboard');
    
    //appointments
    Route::get('/appointments', [PatientAppointmentController::class, 'getPatientAppointments'])->name('appointment');
    Route::post('/appointments/book', [AppointmentController::class, 'book'])->name('appointments.book');
    Route::post('/appointments/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');

    //medical record
    Route::get('/medical-records', [MedicalRecordController::class, 'index'])->name('medical-records');

    //profile
    Route::get('/profile', [PatientController::class, 'getProfile'])->name('profile');
    Route::post('/profile', [PatientController::class, 'updateProfile'])->name('profile.update');

});


//DOCTOR SIDE FUNCTIONS



// Route::prefix('doctor')->name('doctor.')->group(function (){
Route::middleware(['checkauth:doctor'])->prefix('doctor')->name('doctor.')->group(function (){
    Route::get('/dashboard', [DoctorDoctorController::class, 'dashboard'])->name('dashboard');

    // ON BOARDING
    Route::get('/onboarding', [DoctorDoctorController::class, 'showOnBoarding'])->name('onboarding'); 
    Route::post('/onboarding', [DoctorDoctorController::class, 'processOnBoarding'])->name('onboarding.store');

    //SPECIALTY 
    Route::post('/specialty', [DoctorDoctorController::class, 'addSpecialty'])->name('specialty.store');

    //PROFILE
    Route::get('/profile', [DoctorDoctorController::class, 'getProfile'])->name('profile');
    Route::post('/profile', [DoctorDoctorController::class, 'updateProfile'])->name('profile.update');

    //PATIENTS
    Route::get('/patients', [DoctorPatientController::class, 'getPatients'])->name('patients.index');
    Route::post('/appointments/{uuid}/encounter', [DoctorPatientController::class, 'updateEncounter'])->name('appointments.encounter.update');

});



//ADMIN SIDE FUNCTIONS

Route::middleware(['checkauth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('admin.users.index'))->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::post('/users/{id}', [AdminUserController::class, 'update'])->name('users.update');

    Route::get('/roles', [AdminRoleController::class, 'index'])->name('roles.index');
});

