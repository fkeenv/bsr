<?php

use App\Enums\PaymentMethod;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('User Account without a live Membership opens an empty dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('memberships', []));
});

test('User Account with an ended Membership opens the dashboard without that Membership', function () {
    $membership = Membership::factory()->ended()->create();

    $this->actingAs($membership->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('memberships', [])
            ->where('auth.capabilities.isMembershipHolder', false));
});

test('User Account can open the Join a Property page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('join-property'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('join-property/Show'));
});

test('guests are sent to login from the Join a Property page', function () {
    $this->get(route('join-property'))
        ->assertRedirect(route('login'));
});

test('User Account without a live Membership cannot open a Property Statement of Account', function () {
    $user = User::factory()->create();
    $property = Property::factory()->create();

    $this->actingAs($user)
        ->get(route('statement-of-account.show', $property))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('statement-of-account.index'))
        ->assertRedirect(route('dashboard'));
});

test('User Account without a live Membership cannot declare a Payment', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $property = Property::factory()->create();

    $this->actingAs($user)
        ->post(route('payments.store'), [
            'property_id' => $property->id,
            'amount' => '100.00',
            'method' => PaymentMethod::Cash->value,
            'reference' => 'CASH-1',
            'screenshot' => UploadedFile::fake()->image('receipt.jpg'),
        ])
        ->assertForbidden();

    expect(Payment::query()->count())->toBe(0);
});

test('User Account without a live Membership cannot end another Membership', function () {
    $membership = Membership::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('memberships.end', $membership))
        ->assertForbidden();

    expect($membership->refresh()->ended_at)->toBeNull();
});

test('User Account without a live Membership is forbidden from Officer surfaces', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('officer.dashboard'))
        ->assertForbidden();
});

test('Membership Application routes no longer exist', function (string $method, string $uri) {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->call($method, $uri)
        ->assertNotFound();
})->with([
    ['GET', '/membership-application'],
    ['POST', '/membership-application'],
    ['GET', '/officer/membership-applications'],
    ['GET', '/officer/membership-applications/1'],
    ['POST', '/officer/membership-applications/1/approve'],
    ['POST', '/officer/membership-applications/1/reject'],
]);
