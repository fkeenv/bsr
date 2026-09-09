<?php

use App\Http\Controllers\Administrator\DashboardController as AdministratorDashboardController;
use App\Http\Controllers\MembershipApplicationController;
use App\Http\Controllers\Officer\DashboardController;
use App\Http\Middleware\EnsureMembershipOnboardingIsComplete;
use App\Http\Middleware\EnsureRoleSurfaceAccess;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('membership-application', [MembershipApplicationController::class, 'create'])
        ->name('membership-application.create');

    Route::middleware(EnsureMembershipOnboardingIsComplete::class)->group(function () {
        Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    });

    Route::middleware([EnsureRoleSurfaceAccess::class.':officer'])->prefix('officer')->name('officer.')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
    });

    Route::middleware([EnsureRoleSurfaceAccess::class.':administrator'])->prefix('administrator')->name('administrator.')->group(function () {
        Route::get('/', AdministratorDashboardController::class)->name('dashboard');
    });
});

require __DIR__.'/settings.php';
