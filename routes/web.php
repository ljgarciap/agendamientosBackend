<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserController;

Route::get('/company-logos/{filename}', [CompanyController::class, 'getLogo'])->name('company.logo');
Route::get('/profile-photos/{filename}', [UserController::class, 'getProfilePhoto'])->name('user.profile_photo');
Route::get('/service-images/{filename}', [App\Http\Controllers\ServiceController::class, 'getServiceImage'])->name('service.image');

Route::get('/', function () {
    return view('welcome');
});
