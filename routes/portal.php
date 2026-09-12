<?php

use App\Http\Controllers\Portal\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Portal\Auth\RegisteredUserController;
use App\Http\Controllers\Portal\CertificateController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\EnrollmentController;
use App\Http\Controllers\Portal\LandingController;
use App\Http\Controllers\Portal\ProfileController;
use Illuminate\Support\Facades\Route;

// ==========================================================
// TRAINING PORTAL (public registration + trainee dashboard)
// ==========================================================
Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/', [LandingController::class, '__invoke'])->name('landing');

        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store']);

        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
    });

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('portal.auth')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::post('enroll/{training}', [EnrollmentController::class, 'store'])->name('enroll');

        Route::get('certificates', [CertificateController::class, 'index'])->name('certificates');
        Route::get('certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');
    });
});