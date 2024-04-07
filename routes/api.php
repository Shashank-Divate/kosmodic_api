<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\DoctorController;
use App\Http\Middleware\AutenticatePatient;
use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\AuthenticateDoctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes :: To clear laravel cache, config, view and config files
Route::get('clear', function(){
    Artisan::call("cache:clear");
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('config:cache');
    return "Cleared!";
});

// Routes :: User Routes (Patient)
Route::post('/user_login', [UserController::class, 'userLogin']);
Route::post('/verify_user_otp', [UserController::class, 'verifyUserOTP']);
Route::post('/user_update_profile', [UserController::class, 'userUpdateProfile']);

// Routes:: Doctor Routes
Route::post('/doctor_login', [DoctorController::class, 'doctorLogin']);
Route::post('/verify_doctor_otp', [DoctorController::class, 'verifyDoctorOTP']);
Route::post('/doctor_update_profile', [DoctorController::class, 'doctorUpdateProfile']);

//Token verify middleware
Route::middleware([AuthenticateApiToken::class])->group(function () {

    //Middleware to verify whether the role is doctor and only the doctors can access these routes 
    Route::middleware([AuthenticateDoctor::class])->group(function () {

        // Routes:: Doctor Routes
        
    });

    //Middleware to verify whether the role is patient and only the patient can access these routes 
    Route::middleware([AutenticatePatient::class])->group(function(){

        // Routes:: Patient Routes
        Route::get('/doctors_list', [UserController::class, 'doctorsList']);
        Route::get('/doctor_profile_with_time_slots', [UserController::class, 'doctorProfileWithTimeSlots']);
        Route::post('/confirm_slot_selection', [UserController::class, 'confirmSlotSelection']);
    });
});