<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;

use App\Http\Controllers\CompanyController;

// Public routes for Company Selection and Branding
Route::get('/companies', [CompanyController::class, 'index']);
Route::get('/companies/{id}', [CompanyController::class, 'show']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/config', [App\Http\Controllers\ConfigController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::put('/companies/{id}', [CompanyController::class, 'update']);
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);
    
    // Company Admins Management
    Route::get('/companies/{id}/admins', [CompanyController::class, 'indexAdmins']);
    Route::post('/companies/{id}/admins', [CompanyController::class, 'storeAdmin']);
    Route::put('/companies/{companyId}/admins/{userId}', [CompanyController::class, 'updateAdmin']);
    Route::delete('/companies/{companyId}/admins/{userId}', [CompanyController::class, 'destroyAdmin']);

    Route::apiResource('services', ServiceController::class);
    Route::apiResource('employees', \App\Http\Controllers\EmployeeController::class);
    Route::apiResource('appointments', AppointmentController::class);
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index']);
    Route::put('/user/profile', [\App\Http\Controllers\UserController::class, 'updateProfile']);
    Route::post('/appointments/{appointment}/rate', [\App\Http\Controllers\AppointmentController::class, 'rate']);
    
    // Dashboard & Clients
    Route::get('/dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'getStats']);
    Route::get('/company/clients', [\App\Http\Controllers\CompanyClientController::class, 'index']);
    Route::get('/geocode', [\App\Http\Controllers\CompanyController::class, 'geocode']);

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
