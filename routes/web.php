<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Doctor;
use App\Http\Controllers\Patient;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Design preview page, available in local development only.
Route::get('/style-guide', function () {
    abort_unless(app()->environment(['local', 'testing']), 404);

    return view('style-guide');
})->name('style-guide');

// Send each user to the dashboard that matches their role.
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->role->dashboardRoute());
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin area
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');
    Route::resource('doctors', Admin\DoctorController::class)->except('show');
});

// Doctor area
Route::middleware(['auth', 'role:doctor'])->prefix('doctor')->name('doctor.')->group(function () {
    Route::get('/dashboard', Doctor\DashboardController::class)->name('dashboard');
});

// Patient area
Route::middleware(['auth', 'role:patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('/dashboard', Patient\DashboardController::class)->name('dashboard');
});

require __DIR__.'/auth.php';
