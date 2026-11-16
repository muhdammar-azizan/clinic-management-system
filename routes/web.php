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
    Route::get('appointments', [Admin\AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('records', [Admin\MedicalRecordController::class, 'index'])->name('records.index');
    Route::resource('doctors', Admin\DoctorController::class)->except('show');
    Route::get('rosters/calendar', [Admin\RosterSlotController::class, 'calendar'])->name('rosters.calendar');
    Route::resource('rosters', Admin\RosterSlotController::class)->except('show')->parameters(['rosters' => 'slot']);
    Route::resource('patients', Admin\PatientController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
});

// Doctor area
Route::middleware(['auth', 'role:doctor'])->prefix('doctor')->name('doctor.')->group(function () {
    Route::get('/dashboard', Doctor\DashboardController::class)->name('dashboard');
    Route::get('/schedule', Doctor\ScheduleController::class)->name('schedule.index');
    Route::resource('appointments', Doctor\AppointmentController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::get('records/attachments/{attachment}', Doctor\RecordAttachmentController::class)->name('records.attachments.show');
    Route::resource('records', Doctor\MedicalRecordController::class);
});

// Patient area
Route::middleware(['auth', 'role:patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('/dashboard', Patient\DashboardController::class)->name('dashboard');
    Route::get('/book', [Patient\BookingController::class, 'create'])->name('booking.create');
    Route::post('/book', [Patient\BookingController::class, 'store'])->name('booking.store');
    Route::get('/records', [Patient\MedicalRecordController::class, 'index'])->name('records.index');
    Route::get('/records/attachments/{attachment}', [Patient\MedicalRecordController::class, 'download'])->name('records.attachments.show');
    Route::get('/records/{record}', [Patient\MedicalRecordController::class, 'show'])->name('records.show');
    Route::get('/appointments', [Patient\AppointmentController::class, 'index'])->name('appointments.index');
    Route::patch('/appointments/{appointment}/cancel', [Patient\AppointmentController::class, 'cancel'])->name('appointments.cancel');
});

require __DIR__.'/auth.php';
