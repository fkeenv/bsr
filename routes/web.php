<?php

use App\Http\Controllers\Administrator\DashboardController as AdministratorDashboardController;
use App\Http\Controllers\MembershipApplicationController;
use App\Http\Controllers\Officer\DashboardController;
use App\Http\Controllers\Officer\PropertyController;
use App\Http\Controllers\Officer\PropertyImportController;
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

        Route::get('properties/import', [PropertyImportController::class, 'create'])
            ->name('properties.import.create');
        Route::get('properties/import/sample', [PropertyImportController::class, 'sample'])
            ->name('properties.import.sample');
        Route::post('properties/import', [PropertyImportController::class, 'store'])
            ->name('properties.import.store');

        Route::post('properties/{property}/deactivate', [PropertyController::class, 'deactivate'])
            ->name('properties.deactivate');
        Route::post('properties/{property}/activate', [PropertyController::class, 'activate'])
            ->name('properties.activate');

        Route::resource('properties', PropertyController::class)
            ->except(['show']);
    });

    Route::middleware([EnsureRoleSurfaceAccess::class.':administrator'])->prefix('administrator')->name('administrator.')->group(function () {
        Route::get('/', AdministratorDashboardController::class)->name('dashboard');
    });
});

require __DIR__.'/settings.php';
