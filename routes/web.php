<?php

use App\Http\Controllers\Administrator\DashboardController as AdministratorDashboardController;
use App\Http\Controllers\Administrator\OfficerController as AdministratorOfficerController;
use App\Http\Controllers\AnnouncementAttachmentController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\MembershipApplicationController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\Officer\AnnouncementAttachmentController as OfficerAnnouncementAttachmentController;
use App\Http\Controllers\Officer\AnnouncementController as OfficerAnnouncementController;
use App\Http\Controllers\Officer\BillSettingsController;
use App\Http\Controllers\Officer\ChargeController;
use App\Http\Controllers\Officer\ChargeGenerationController;
use App\Http\Controllers\Officer\DashboardController as OfficerDashboardController;
use App\Http\Controllers\Officer\FeeTypeController;
use App\Http\Controllers\Officer\LevySettingsController;
use App\Http\Controllers\Officer\MembershipApplicationController as OfficerMembershipApplicationController;
use App\Http\Controllers\Officer\MembershipController as OfficerMembershipController;
use App\Http\Controllers\Officer\PaymentController as OfficerPaymentController;
use App\Http\Controllers\Officer\PrintedBillController;
use App\Http\Controllers\Officer\PropertyController;
use App\Http\Controllers\Officer\PropertyImportController;
use App\Http\Controllers\Officer\SuspendController;
use App\Http\Controllers\Officer\UnpaidRosterController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StatementOfAccountController;
use App\Http\Controllers\SuperAdmin\AdministratorController as SuperAdminAdministratorController;
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

Route::get('announcements', [AnnouncementController::class, 'index'])
    ->name('announcements.index');
Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])
    ->name('announcements.show');
Route::get('announcement-attachments/{attachment}', [AnnouncementAttachmentController::class, 'show'])
    ->name('announcements.attachments.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('membership-application', [MembershipApplicationController::class, 'create'])
        ->name('membership-application.create');
    Route::post('membership-application', [MembershipApplicationController::class, 'store'])
        ->name('membership-application.store');

    Route::middleware(EnsureMembershipOnboardingIsComplete::class)->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::post('memberships/{membership}/end', [MembershipController::class, 'end'])
            ->name('memberships.end');

        Route::post('payments', [PaymentController::class, 'store'])
            ->name('payments.store');

        Route::get('statement-of-account', [StatementOfAccountController::class, 'index'])
            ->name('statement-of-account.index');
        Route::get('properties/{property}/statement-of-account', [StatementOfAccountController::class, 'show'])
            ->name('statement-of-account.show');
    });

    Route::middleware([EnsureRoleSurfaceAccess::class.':officer'])->prefix('officer')->name('officer.')->group(function () {
        Route::get('/', OfficerDashboardController::class)->name('dashboard');

        Route::get('announcements', [OfficerAnnouncementController::class, 'index'])
            ->name('announcements.index');
        Route::get('announcements/create', [OfficerAnnouncementController::class, 'create'])
            ->name('announcements.create');
        Route::post('announcements', [OfficerAnnouncementController::class, 'store'])
            ->name('announcements.store');
        Route::get('announcements/{announcement}/edit', [OfficerAnnouncementController::class, 'edit'])
            ->name('announcements.edit');
        Route::put('announcements/{announcement}', [OfficerAnnouncementController::class, 'update'])
            ->name('announcements.update');
        Route::post('announcements/{announcement}/publish', [OfficerAnnouncementController::class, 'publish'])
            ->name('announcements.publish');
        Route::post('announcements/{announcement}/unpublish', [OfficerAnnouncementController::class, 'unpublish'])
            ->name('announcements.unpublish');
        Route::post('announcements/{announcement}/pin', [OfficerAnnouncementController::class, 'pin'])
            ->name('announcements.pin');
        Route::post('announcements/{announcement}/unpin', [OfficerAnnouncementController::class, 'unpin'])
            ->name('announcements.unpin');
        Route::post('announcements/{announcement}/attachments', [OfficerAnnouncementAttachmentController::class, 'store'])
            ->name('announcements.attachments.store');
        Route::put('announcements-page-visibility', [OfficerAnnouncementController::class, 'updatePageVisibility'])
            ->name('announcements.page-visibility.update');

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

        Route::get('payments', [OfficerPaymentController::class, 'index'])
            ->name('payments.index');
        Route::get('payments/create', [OfficerPaymentController::class, 'create'])
            ->name('payments.create');
        Route::post('payments', [OfficerPaymentController::class, 'store'])
            ->name('payments.store');
        Route::post('payments/{payment}/confirm', [OfficerPaymentController::class, 'confirm'])
            ->name('payments.confirm');
        Route::post('payments/{payment}/reject', [OfficerPaymentController::class, 'reject'])
            ->name('payments.reject');
        Route::post('payments/{payment}/void', [OfficerPaymentController::class, 'void'])
            ->name('payments.void');

        Route::get('unpaid', [UnpaidRosterController::class, 'index'])
            ->name('unpaid.index');

        Route::get('printed-bills/batch', [PrintedBillController::class, 'batch'])
            ->name('printed-bills.batch');
        Route::get('printed-bills/{property}', [PrintedBillController::class, 'show'])
            ->name('printed-bills.show');

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

        Route::get('bill-settings', [BillSettingsController::class, 'edit'])
            ->name('bill-settings.edit');
        Route::put('bill-settings', [BillSettingsController::class, 'update'])
            ->name('bill-settings.update');
    });

    Route::middleware([EnsureRoleSurfaceAccess::class.':administrator'])->prefix('administrator')->name('administrator.')->group(function () {
        Route::get('/', AdministratorDashboardController::class)->name('dashboard');

        Route::get('officers', [AdministratorOfficerController::class, 'index'])
            ->name('officers.index');
        Route::post('officers', [AdministratorOfficerController::class, 'store'])
            ->name('officers.store');
    });

    Route::middleware([EnsureRoleSurfaceAccess::class.':super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/', SuperAdminDashboardController::class)->name('dashboard');

        Route::get('administrators', [SuperAdminAdministratorController::class, 'index'])
            ->name('administrators.index');
        Route::post('administrators', [SuperAdminAdministratorController::class, 'store'])
            ->name('administrators.store');

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
