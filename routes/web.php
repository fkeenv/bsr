<?php

use App\Http\Controllers\Administrator\DashboardController as AdministratorDashboardController;
use App\Http\Controllers\Administrator\OfficerController as AdministratorOfficerController;
use App\Http\Controllers\Administrator\UserAccountController;
use App\Http\Controllers\AnnouncementAttachmentController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventAttendeeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventFinancialController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\EventTicketController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\Officer\AnnouncementAttachmentController as OfficerAnnouncementAttachmentController;
use App\Http\Controllers\Officer\AnnouncementController as OfficerAnnouncementController;
use App\Http\Controllers\Officer\BillSettingsController;
use App\Http\Controllers\Officer\ChargeController;
use App\Http\Controllers\Officer\ChargeGenerationController;
use App\Http\Controllers\Officer\DashboardController as OfficerDashboardController;
use App\Http\Controllers\Officer\EventCheckInController;
use App\Http\Controllers\Officer\EventController as OfficerEventController;
use App\Http\Controllers\Officer\EventRegistrationController as OfficerEventRegistrationController;
use App\Http\Controllers\Officer\FeeTypeController;
use App\Http\Controllers\Officer\LevySettingsController;
use App\Http\Controllers\Officer\MembershipController as OfficerMembershipController;
use App\Http\Controllers\Officer\PaymentController as OfficerPaymentController;
use App\Http\Controllers\Officer\PrintedBillController;
use App\Http\Controllers\Officer\PropertyController;
use App\Http\Controllers\Officer\PropertyImportController;
use App\Http\Controllers\Officer\PropertyInvitationController;
use App\Http\Controllers\Officer\SuspendController;
use App\Http\Controllers\Officer\UnpaidRosterController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Owner\PropertyInvitationController as OwnerPropertyInvitationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PropertyInvitationCodeController;
use App\Http\Controllers\PropertyInvitationRedemptionController;
use App\Http\Controllers\PropertyProfileController;
use App\Http\Controllers\StatementOfAccountController;
use App\Http\Controllers\SuperAdmin\AccessHistoryController;
use App\Http\Controllers\SuperAdmin\AdministratorController as SuperAdminAdministratorController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\LegalDocumentController as SuperAdminLegalDocumentController;
use App\Http\Controllers\SuperAdmin\RolePermissionController;
use App\Http\Controllers\WelcomeController;
use App\Http\Middleware\EnsureAnnouncementPermission;
use App\Http\Middleware\EnsureRoleSurfaceAccess;
use App\Http\Middleware\EnsureStaffPermission;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

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

Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('events/{event}/image', [EventController::class, 'image'])->name('events.image');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::put('event-attendees/{attendee}', [EventAttendeeController::class, 'update'])->name('event-attendees.update');
    Route::get('event-tickets/{attendee:ticket_token}', [EventTicketController::class, 'show'])->name('event-tickets.show');
    Route::get('event-tickets/{attendee:ticket_token}/qr', [EventTicketController::class, 'qr'])->name('event-tickets.qr');
    Route::get('officer/event-registrations', [OfficerEventRegistrationController::class, 'index'])->middleware(EnsureStaffPermission::class.':events.registrations.read')->name('officer.event-registrations.index');

    Route::post('event-registrations/{registration}/payment', [EventFinancialController::class, 'submit'])->name('event-registrations.payment');
    Route::post('event-registrations/{registration}/cancellation-requests', [EventFinancialController::class, 'requestCancellation'])->name('event-registrations.request-cancellation');
    Route::get('event-payments/{payment}/receipt', [EventFinancialController::class, 'receipt'])->name('event-payments.receipt');
    Route::get('officer/event-finances', [App\Http\Controllers\Officer\EventFinancialController::class, 'index'])->middleware(EnsureStaffPermission::class.':events.payments.review,events.refunds.review')->name('officer.event-finances.index');
    Route::post('officer/event-payments/{payment}/review', [App\Http\Controllers\Officer\EventFinancialController::class, 'review'])->middleware(EnsureStaffPermission::class.':events.payments.review')->name('officer.event-payments.review');
    Route::post('officer/event-cancellations/{cancellation}/resolve', [App\Http\Controllers\Officer\EventFinancialController::class, 'resolve'])->middleware(EnsureStaffPermission::class.':events.refunds.review')->name('officer.event-cancellations.resolve');
    Route::post('officer/event-payments/{payment}/refunds', [App\Http\Controllers\Officer\EventFinancialController::class, 'refund'])->middleware(EnsureStaffPermission::class.':events.refunds.review')->name('officer.event-payments.refund');

    Route::prefix('officer/event-check-in')->name('officer.event-check-in.')->middleware(EnsureStaffPermission::class.':events.check-in')->group(function () {
        Route::get('/', [EventCheckInController::class, 'index'])->name('index');
        Route::get('{event}', [EventCheckInController::class, 'show'])->name('show');
        Route::post('{event}', [EventCheckInController::class, 'store'])->name('store');
    });

    Route::post('events/{event}/registrations', [EventRegistrationController::class, 'store'])->name('events.registrations.store');
    Route::get('event-registrations', [EventRegistrationController::class, 'index'])->name('event-registrations.index');
    Route::get('event-registrations/{registration}', [EventRegistrationController::class, 'show'])->name('event-registrations.show');
    Route::post('event-registrations/{registration}/cancel', [EventRegistrationController::class, 'cancel'])->name('event-registrations.cancel');

    Route::prefix('officer/events')->name('officer.events.')->middleware(EnsureStaffPermission::class.':events.manage')->group(function () {
        Route::get('/', [OfficerEventController::class, 'index'])->name('index');
        Route::get('create', [OfficerEventController::class, 'create'])->name('create');
        Route::post('/', [OfficerEventController::class, 'store'])->name('store');
        Route::get('{event}/edit', [OfficerEventController::class, 'edit'])->name('edit');
        Route::put('{event}', [OfficerEventController::class, 'update'])->name('update');
        Route::post('{event}/publish', [OfficerEventController::class, 'publish'])->name('publish');
        Route::post('{event}/cancel', [OfficerEventController::class, 'cancel'])->name('cancel');
    });

    Route::get('property-invitations/{token}', [PropertyInvitationRedemptionController::class, 'show'])
        ->where('token', '[A-Za-z0-9]{64}|[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-4[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}')
        ->middleware('throttle:property-invitation-lookup')
        ->name('property-invitations.show');
    Route::post('property-invitations/{token}', [PropertyInvitationRedemptionController::class, 'store'])
        ->where('token', '[A-Za-z0-9]{64}|[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-4[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}')
        ->middleware('throttle:property-invitation-redemption')
        ->name('property-invitations.store');

    Route::post('property-invitation-codes', [PropertyInvitationCodeController::class, 'store'])
        ->middleware('throttle:property-invitation-lookup')
        ->name('property-invitation-codes.store');

    Route::inertia('join-property', 'join-property/Show')
        ->name('join-property');

    Route::get('properties/{property}/profile', [PropertyProfileController::class, 'edit'])
        ->name('property-profile.edit');
    Route::get('properties/{property}/invitations', [OwnerPropertyInvitationController::class, 'index'])
        ->name('owner.property-invitations.index');
    Route::post('properties/{property}/invitations', [OwnerPropertyInvitationController::class, 'store'])
        ->name('owner.property-invitations.store');
    Route::delete('properties/{property}/invitations/{propertyInvitation}', [OwnerPropertyInvitationController::class, 'destroy'])
        ->name('owner.property-invitations.destroy');
    Route::put('properties/{property}/profile', [PropertyProfileController::class, 'update'])
        ->name('property-profile.update');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('onboarding/{experience}/tour-acknowledgement', [OnboardingController::class, 'acknowledgeTour'])
        ->name('onboarding.tour-acknowledgement.store');
    Route::post('onboarding/{experience}/steps', [OnboardingController::class, 'completeStep'])
        ->name('onboarding.steps.store');

    Route::post('memberships/{membership}/end', [MembershipController::class, 'end'])
        ->name('memberships.end');

    Route::post('payments', [PaymentController::class, 'store'])
        ->name('payments.store');

    Route::get('statement-of-account', [StatementOfAccountController::class, 'index'])
        ->name('statement-of-account.index');
    Route::get('properties/{property}/statement-of-account', [StatementOfAccountController::class, 'show'])
        ->name('statement-of-account.show');

    Route::prefix('officer')->name('officer.')->group(function () {
        Route::get('announcements', [OfficerAnnouncementController::class, 'index'])
            ->middleware(EnsureAnnouncementPermission::class.':announcements.read')
            ->name('announcements.index');
        Route::get('announcements/create', [OfficerAnnouncementController::class, 'create'])
            ->middleware(EnsureAnnouncementPermission::class.':announcements.create')
            ->name('announcements.create');
        Route::post('announcements', [OfficerAnnouncementController::class, 'store'])
            ->middleware(EnsureAnnouncementPermission::class.':announcements.create')
            ->name('announcements.store');
        Route::get('announcements/{announcement}/edit', [OfficerAnnouncementController::class, 'edit'])
            ->middleware(EnsureAnnouncementPermission::class.':announcements.read,announcements.edit')
            ->name('announcements.edit');
        Route::put('announcements/{announcement}', [OfficerAnnouncementController::class, 'update'])
            ->middleware(EnsureAnnouncementPermission::class.':announcements.edit')
            ->name('announcements.update');
        Route::post('announcements/{announcement}/publish', [OfficerAnnouncementController::class, 'publish'])
            ->middleware(EnsureAnnouncementPermission::class.':announcements.publish')
            ->name('announcements.publish');
        Route::post('announcements/{announcement}/unpublish', [OfficerAnnouncementController::class, 'unpublish'])
            ->middleware(EnsureAnnouncementPermission::class.':announcements.publish')
            ->name('announcements.unpublish');
        Route::post('announcements/{announcement}/pin', [OfficerAnnouncementController::class, 'pin'])
            ->middleware(EnsureAnnouncementPermission::class.':announcements.pin')
            ->name('announcements.pin');
        Route::post('announcements/{announcement}/unpin', [OfficerAnnouncementController::class, 'unpin'])
            ->middleware(EnsureAnnouncementPermission::class.':announcements.pin')
            ->name('announcements.unpin');
        Route::post('announcements/{announcement}/attachments', [OfficerAnnouncementAttachmentController::class, 'store'])
            ->middleware(EnsureAnnouncementPermission::class.':announcements.attachments')
            ->name('announcements.attachments.store');

    });

    Route::prefix('officer')->name('officer.')->group(function () {
        Route::get('memberships', [OfficerMembershipController::class, 'index'])
            ->middleware(EnsureStaffPermission::class.':memberships.read')
            ->name('memberships.index');
        Route::put('memberships/{membership}/role', [OfficerMembershipController::class, 'updateRole'])
            ->middleware(EnsureStaffPermission::class.':memberships.change-role')
            ->name('memberships.update-role');
        Route::post('memberships/{membership}/end', [OfficerMembershipController::class, 'end'])
            ->middleware(EnsureStaffPermission::class.':memberships.end')
            ->name('memberships.end');

        Route::get('property-invitations', [PropertyInvitationController::class, 'index'])
            ->middleware(EnsureStaffPermission::class.':invitations.read')
            ->name('property-invitations.index');
        Route::get('property-invitations/create', [PropertyInvitationController::class, 'create'])
            ->middleware(EnsureStaffPermission::class.':invitations.create')->name('property-invitations.create');
        Route::post('property-invitations', [PropertyInvitationController::class, 'store'])
            ->middleware(EnsureStaffPermission::class.':invitations.create')
            ->name('property-invitations.store');
        Route::delete('property-invitations/{propertyInvitation}', [PropertyInvitationController::class, 'destroy'])
            ->middleware(EnsureStaffPermission::class.':invitations.revoke')
            ->name('property-invitations.destroy');
        Route::get('property-invitations/{propertyInvitation}/share', [PropertyInvitationController::class, 'share'])
            ->middleware(EnsureStaffPermission::class.':invitations.credentials')
            ->name('property-invitations.share');

        Route::get('properties/import', [PropertyImportController::class, 'create'])
            ->middleware(EnsureStaffPermission::class.':properties.import,properties.import-sample')
            ->name('properties.import.create');
        Route::get('properties/import/sample', [PropertyImportController::class, 'sample'])
            ->middleware(EnsureStaffPermission::class.':properties.import-sample')
            ->name('properties.import.sample');
        Route::post('properties/import', [PropertyImportController::class, 'store'])
            ->middleware(EnsureStaffPermission::class.':properties.import')
            ->name('properties.import.store');

        Route::post('properties/{property}/deactivate', [PropertyController::class, 'deactivate'])
            ->middleware(EnsureStaffPermission::class.':properties.deactivate')
            ->name('properties.deactivate');
        Route::post('properties/{property}/activate', [PropertyController::class, 'activate'])
            ->middleware(EnsureStaffPermission::class.':properties.reactivate')
            ->name('properties.activate');

        Route::resource('properties', PropertyController::class)
            ->except(['show'])
            ->middlewareFor('index', EnsureStaffPermission::class.':properties.read')
            ->middlewareFor(['create', 'store'], EnsureStaffPermission::class.':properties.create')
            ->middlewareFor(['edit', 'update'], EnsureStaffPermission::class.':properties.edit')
            ->middlewareFor('destroy', EnsureStaffPermission::class.':properties.delete');

        Route::resource('suspends', SuspendController::class)
            ->except(['show', 'destroy'])
            ->middlewareFor('index', EnsureStaffPermission::class.':suspends.read')
            ->middlewareFor(['create', 'store'], EnsureStaffPermission::class.':suspends.create')
            ->middlewareFor(['edit', 'update'], EnsureStaffPermission::class.':suspends.edit');

    });

    Route::middleware([EnsureRoleSurfaceAccess::class.':officer'])->prefix('officer')->name('officer.')->group(function () {
        Route::get('/', OfficerDashboardController::class)->name('dashboard');
    });

    Route::prefix('officer')->name('officer.')->group(function () {
        Route::get('payments', [OfficerPaymentController::class, 'index'])
            ->middleware(EnsureStaffPermission::class.':payments.read')
            ->name('payments.index');
        Route::get('payments/create', [OfficerPaymentController::class, 'create'])
            ->middleware(EnsureStaffPermission::class.':payments.create')
            ->name('payments.create');
        Route::post('payments', [OfficerPaymentController::class, 'store'])
            ->middleware(EnsureStaffPermission::class.':payments.create')
            ->name('payments.store');
        Route::post('payments/{payment}/confirm', [OfficerPaymentController::class, 'confirm'])
            ->middleware(EnsureStaffPermission::class.':payments.confirm')
            ->name('payments.confirm');
        Route::post('payments/{payment}/reject', [OfficerPaymentController::class, 'reject'])
            ->middleware(EnsureStaffPermission::class.':payments.reject')
            ->name('payments.reject');
        Route::post('payments/{payment}/void', [OfficerPaymentController::class, 'void'])
            ->middleware(EnsureStaffPermission::class.':payments.void')
            ->name('payments.void');

        Route::get('unpaid', [UnpaidRosterController::class, 'index'])
            ->middleware(EnsureStaffPermission::class.':unpaid.read')
            ->name('unpaid.index');

        Route::get('printed-bills/batch', [PrintedBillController::class, 'batch'])
            ->middleware(EnsureStaffPermission::class.':printed-bills.batch')
            ->name('printed-bills.batch');
        Route::get('printed-bills/{property}', [PrintedBillController::class, 'show'])
            ->middleware(EnsureStaffPermission::class.':printed-bills.single')
            ->name('printed-bills.show');

        Route::resource('fee-types', FeeTypeController::class)
            ->except(['show', 'destroy'])
            ->middlewareFor('index', EnsureStaffPermission::class.':fee-types.read')
            ->middlewareFor(['create', 'store'], EnsureStaffPermission::class.':fee-types.create')
            ->middlewareFor(['edit', 'update'], EnsureStaffPermission::class.':fee-types.edit');

        Route::get('charges/generate', [ChargeGenerationController::class, 'create'])
            ->middleware(EnsureStaffPermission::class.':charges.generate')
            ->name('charges.generate.create');
        Route::post('charges/generate', [ChargeGenerationController::class, 'store'])
            ->middleware(EnsureStaffPermission::class.':charges.generate')
            ->name('charges.generate');

        Route::resource('charges', ChargeController::class)
            ->only(['index', 'edit', 'update'])
            ->middlewareFor('index', EnsureStaffPermission::class.':charges.read')
            ->middlewareFor(['edit', 'update'], EnsureStaffPermission::class.':charges.edit');

        Route::get('levy-settings', [LevySettingsController::class, 'edit'])
            ->middleware(EnsureStaffPermission::class.':levy-settings.read,levy-settings.edit')
            ->name('levy-settings.edit');
        Route::put('levy-settings', [LevySettingsController::class, 'update'])
            ->middleware(EnsureStaffPermission::class.':levy-settings.edit')
            ->name('levy-settings.update');

        Route::get('bill-settings', [BillSettingsController::class, 'edit'])
            ->middleware(EnsureStaffPermission::class.':bill-settings.read,bill-settings.edit')
            ->name('bill-settings.edit');
        Route::put('bill-settings', [BillSettingsController::class, 'update'])
            ->middleware(EnsureStaffPermission::class.':bill-settings.edit')
            ->name('bill-settings.update');
    });

    Route::middleware([EnsureRoleSurfaceAccess::class.':administrator'])->prefix('administrator')->name('administrator.')->group(function () {
        Route::get('/', AdministratorDashboardController::class)->name('dashboard');

        Route::post('user-accounts/{user}/staff-roles/{role:name}', [UserAccountController::class, 'assignRole'])->name('user-accounts.staff-roles.store');
        Route::delete('user-accounts/{user}/staff-roles/{role:name}', [UserAccountController::class, 'revokeRole'])->name('user-accounts.staff-roles.destroy');
        Route::post('user-accounts/{user}/memberships/{membership}/end', [UserAccountController::class, 'endMembership'])->name('user-accounts.memberships.end');
        Route::put('user-accounts/{user}/memberships/{membership}/role', [UserAccountController::class, 'changeMembershipRole'])->name('user-accounts.memberships.update-role');
        Route::get('user-accounts', [UserAccountController::class, 'index'])->name('user-accounts.index');
        Route::get('user-accounts/{user}', [UserAccountController::class, 'show'])->name('user-accounts.show');

        Route::get('officers', [AdministratorOfficerController::class, 'index'])
            ->name('officers.index');
        Route::post('officers', [AdministratorOfficerController::class, 'store'])
            ->name('officers.store');
    });

    Route::middleware([EnsureRoleSurfaceAccess::class.':super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('access-history', [AccessHistoryController::class, 'index'])->name('access-history.index');
        Route::get('role-permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');
        Route::post('role-permissions', [RolePermissionController::class, 'store'])->name('role-permissions.store');
        Route::patch('role-permissions/{role:name}/name', [RolePermissionController::class, 'rename'])->name('role-permissions.rename');
        Route::delete('role-permissions/{role:name}', [RolePermissionController::class, 'destroy'])->name('role-permissions.destroy');
        Route::put('role-permissions/{role:name}', [RolePermissionController::class, 'update'])->name('role-permissions.update');
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
