<?php

use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentVersion;
use App\Models\User;

test('guest can read the current Terms of Service with an identifiable version', function () {
    $version = LegalDocumentVersion::factory()->create([
        'type' => LegalDocumentType::TermsOfService,
        'body' => 'These are the current terms.',
    ]);

    $this->get(route('terms-of-service.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('legal/TermsOfService')
            ->where('document.id', $version->id)
            ->where('document.body', 'These are the current terms.')
        );
});

test('guest can read the current Privacy Policy with an identifiable version', function () {
    $version = LegalDocumentVersion::factory()->create([
        'type' => LegalDocumentType::PrivacyPolicy,
        'body' => 'This is the current privacy policy.',
    ]);

    $this->get(route('privacy-policy.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('legal/PrivacyPolicy')
            ->where('document.id', $version->id)
            ->where('document.body', 'This is the current privacy policy.')
        );
});

test('public pages show the latest published version for each document type', function () {
    LegalDocumentVersion::factory()->create([
        'type' => LegalDocumentType::TermsOfService,
        'body' => 'Old terms',
        'published_at' => now()->subDay(),
    ]);

    $current = LegalDocumentVersion::factory()->create([
        'type' => LegalDocumentType::TermsOfService,
        'body' => 'New terms',
        'published_at' => now(),
    ]);

    $this->get(route('terms-of-service.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('legal/TermsOfService')
            ->where('document.id', $current->id)
            ->where('document.body', 'New terms')
        );
});

test('Super Admin can publish a new Terms of Service version', function () {
    $user = User::factory()->superAdmin()->create();

    LegalDocumentVersion::factory()->create([
        'type' => LegalDocumentType::TermsOfService,
        'body' => '<p>Old terms</p>',
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->put(route('super-admin.terms-of-service.update'), [
            'body' => '<p>Updated <strong>association</strong> terms.</p>',
        ])
        ->assertRedirect(route('super-admin.terms-of-service.edit'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Terms of Service published.',
        ]);

    $current = LegalDocumentVersion::query()
        ->where('type', LegalDocumentType::TermsOfService)
        ->latest('published_at')
        ->first();

    expect($current)->not->toBeNull()
        ->and($current->body)->toBe('<p>Updated <strong>association</strong> terms.</p>');

    $this->get(route('terms-of-service.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('document.id', $current->id)
            ->where('document.body', '<p>Updated <strong>association</strong> terms.</p>')
        );
});

test('Super Admin can publish a new Privacy Policy version', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->put(route('super-admin.privacy-policy.update'), [
            'body' => '<p>Updated privacy notice.</p>',
        ])
        ->assertRedirect(route('super-admin.privacy-policy.edit'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Privacy Policy published.',
        ]);

    $this->assertDatabaseHas('legal_document_versions', [
        'type' => LegalDocumentType::PrivacyPolicy->value,
        'body' => '<p>Updated privacy notice.</p>',
    ]);
});

test('published legal document HTML is sanitized', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->put(route('super-admin.terms-of-service.update'), [
            'body' => '<p>Safe</p><script>alert(1)</script><p onclick="x()">Click</p>',
        ])
        ->assertRedirect(route('super-admin.terms-of-service.edit'));

    $current = LegalDocumentVersion::query()
        ->where('type', LegalDocumentType::TermsOfService)
        ->latest('published_at')
        ->firstOrFail();

    expect($current->body)->toBe('<p>Safe</p>alert(1)<p>Click</p>')
        ->and($current->body)->not->toContain('<script>')
        ->and($current->body)->not->toContain('onclick');
});

test('empty TipTap body is rejected', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->from(route('super-admin.terms-of-service.edit'))
        ->put(route('super-admin.terms-of-service.update'), [
            'body' => '<p></p>',
        ])
        ->assertRedirect(route('super-admin.terms-of-service.edit'))
        ->assertSessionHasErrors('body');
});

test('Super Admin can open the Terms of Service editor with the current body', function () {
    $user = User::factory()->superAdmin()->create();
    $version = LegalDocumentVersion::factory()->create([
        'type' => LegalDocumentType::TermsOfService,
        'body' => 'Editable terms body',
    ]);

    $this->actingAs($user)
        ->get(route('super-admin.terms-of-service.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('super-admin/legal/EditTermsOfService')
            ->where('document.id', $version->id)
            ->where('document.body', 'Editable terms body')
        );
});

test('Officer cannot publish Terms of Service', function () {
    $user = User::factory()->officer()->create();

    $this->actingAs($user)
        ->put(route('super-admin.terms-of-service.update'), [
            'body' => 'Unauthorized terms.',
        ])
        ->assertForbidden();
});

test('Officer cannot open the Terms of Service editor', function () {
    $user = User::factory()->officer()->create();

    $this->actingAs($user)
        ->get(route('super-admin.terms-of-service.edit'))
        ->assertForbidden();
});

test('Administrator cannot publish Privacy Policy', function () {
    $user = User::factory()->administrator()->create();

    $this->actingAs($user)
        ->put(route('super-admin.privacy-policy.update'), [
            'body' => 'Unauthorized privacy.',
        ])
        ->assertForbidden();
});

test('Administrator cannot open the Privacy Policy editor', function () {
    $user = User::factory()->administrator()->create();

    $this->actingAs($user)
        ->get(route('super-admin.privacy-policy.edit'))
        ->assertForbidden();
});

test('plain User Account cannot open the Terms of Service editor', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.terms-of-service.edit'))
        ->assertRedirect(route('membership-application.create'));
});
