<?php

use App\Http\Controllers\Administrator\DashboardController as AdministratorDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\MembershipApplicationController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\Officer\ChargeController;
use App\Http\Controllers\Officer\ChargeGenerationController;
use App\Http\Controllers\Officer\DashboardController as OfficerDashboardController;
use App\Http\Controllers\Officer\FeeTypeController;
use App\Http\Controllers\Officer\LevySettingsController;
use App\Http\Controllers\Officer\MembershipApplicationController as OfficerMembershipApplicationController;
use App\Http\Controllers\Officer\MembershipController as OfficerMembershipController;
use App\Http\Controllers\Officer\PropertyController;
use App\Http\Controllers\Officer\PropertyImportController;
use App\Http\Controllers\Officer\SuspendController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\LegalDocumentController as SuperAdminLegalDocumentController;
use App\Http\Middleware\EnsureMembershipOnboardingIsComplete;
use App\Http\Middleware\EnsureRoleSurfaceAccess;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('terms-of-service', [LegalDocumentController::class, 'termsOfService'])
    ->name('terms-of-service.show');
Route::get('privacy-policy', [LegalDocumentController::class, 'privacyPolicy'])
    ->name('privacy-policy.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('membership-application', [MembershipApplicationController::class, 'create'])
        ->name('membership-application.create');
    Route::post('membership-application', [MembershipApplicationController::class, 'store'])
        ->name('membership-application.store');

    Route::middleware(EnsureMembershipOnboardingIsComplete::class)->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::post('memberships/{membership}/end', [MembershipController::class, 'end'])
            ->name('memberships.end');
    });

    Route::middleware([EnsureRoleSurfaceAccess::class.':officer'])->prefix('officer')->name('officer.')->group(function () {
        Route::get('/', OfficerDashboardController::class)->name('dashboard');

        Route::get('membership-applications', [OfficerMembershipApplicationController::class, 'index'])
            ->name('membership-applications.index');
        Route::get('membership-applications/{membershipApplication}', [OfficerMembershipApplicationController::class, 'show'])
            ->name('membership-applications.show');
        Route::post('membership-applications/{membershipApplication}/approve', [OfficerMembershipApplicationController::class, 'approve'])
            ->name('membership-applications.approve');
        Route::post('membership-applications/{membershipApplication}/reject', [OfficerMembershipApplicationController::class, 'reject'])
            ->name('membership-applications.reject');

        Route::get('memberships', [OfficerMembershipController::class, 'index'])
            ->name('memberships.index');
        Route::put('memberships/{membership}/role', [OfficerMembershipController::class, 'updateRole'])
            ->name('memberships.update-role');
        Route::post('memberships/{membership}/end', [OfficerMembershipController::class, 'end'])
            ->name('memberships.end');

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

        Route::resource('fee-types', FeeTypeController::class)
            ->except(['show', 'destroy']);

        Route::resource('suspends', SuspendController::class)
            ->except(['show', 'destroy']);

        Route::get('charges/generate', [ChargeGenerationController::class, 'create'])
            ->name('charges.generate.create');
        Route::post('charges/generate', [ChargeGenerationController::class, 'store'])
            ->name('charges.generate');

        Route::resource('charges', ChargeController::class)
            ->only(['index', 'edit', 'update']);

        Route::get('levy-settings', [LevySettingsController::class, 'edit'])
            ->name('levy-settings.edit');
        Route::put('levy-settings', [LevySettingsController::class, 'update'])
            ->name('levy-settings.update');
    });

    Route::middleware([EnsureRoleSurfaceAccess::class.':administrator'])->prefix('administrator')->name('administrator.')->group(function () {
        Route::get('/', AdministratorDashboardController::class)->name('dashboard');
    });

    Route::middleware([EnsureRoleSurfaceAccess::class.':super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/', SuperAdminDashboardController::class)->name('dashboard');

        Route::get('terms-of-service', [SuperAdminLegalDocumentController::class, 'editTermsOfService'])
            ->name('terms-of-service.edit');
        Route::put('terms-of-service', [SuperAdminLegalDocumentController::class, 'updateTermsOfService'])
            ->name('terms-of-service.update');

        Route::get('privacy-policy', [SuperAdminLegalDocumentController::class, 'editPrivacyPolicy'])
            ->name('privacy-policy.edit');
        Route::put('privacy-policy', [SuperAdminLegalDocumentController::class, 'updatePrivacyPolicy'])
            ->name('privacy-policy.update');
    });
});

require __DIR__.'/settings.php';
