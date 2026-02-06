<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // Optional but good to have

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('appointments', AppointmentController::class);
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index']);
    Route::post('/appointments/{appointment}/rate', [\App\Http\Controllers\AppointmentController::class, 'rate']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
