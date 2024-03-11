<?php

use App\Http\Controllers\UserController;
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