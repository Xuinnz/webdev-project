<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controller\AuthController;
use App\Http\Controller\ProfileController;
use App\Http\Controller\AppointmentController;
use App\Http\Controller\ChatController;


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
Route::group(['prefix' => 'profile'], function() {
    Route::get('/', [ProfileController::class, 'getProfile'])->name('user.get');
    
    Route::post('/', [ProfileController::class], 'editProfile')->name('user.edit');
});


//SHARED FUNCTIONS
Route::group(['prefix' => 'appointments'], function() {
    Route::get('/{uuid}', [AppointmentController::class, 'getAppointment'])->name('appointment.get');

    Route::post('/{uuid}', [AppointmentController::class, 'editAppointment'])->name('appointment.edit');
});

//SHARED FUNCTIONS
Route::group(['prefix' => 'conversations'], function() {
    Route::get('/', [ChatController::class, 'getConversations'])->name('convo.getAll');

    Route::post('/', [ChatController::class, 'startConversation'])->name('convo.start');

    Route::get('/{uuid}', [ChatController::class, 'getConversation'])->name('convo.get');

    Route::get('/{uuid}/messages', [ChatController::class, 'getMessages'])->name('convo.getMessages');

    Route::post('/{uuid}/messages', [ChatController::class, 'sendMessages'])->name('convo.sendMessages');
});

//PATIENT SIDE FUNCTIONS
Route::middleware(['checkauth:patient'])->prefix('patient')->name('patient.')->group(function (){

});

//DOCTOR SIDE FUNCTIONS
Route::middleware(['checkauth:doctor'])->prefix('doctor')->name('doctor.')->group(function (){

});





