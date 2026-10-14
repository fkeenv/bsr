<?php

use App\Actions\Payments\ConfirmPayment;
use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\ChargeLine;
use App\Models\FeeType;
use App\Models\OnboardingProgress;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Permission\Models\Role;

test('staff Payment reading requires an explicit role grant', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['announcements.read']);
    $this->actingAs($officer)->get(route('officer.payments.index'))->assertForbidden();
});

test('financial listing grants deny direct mutations and Printed Bill downloads', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions([
        'payments.read', 'charges.read', 'unpaid.read', 'fee-types.read', 'levy-settings.read', 'bill-settings.read',
    ]);
    $payment = Payment::factory()->create();
    $charge = Charge::factory()->create();
    $feeType = FeeType::factory()->create();
    foreach ([
        ['get', 'payments.create', []], ['post', 'payments.store', []],
        ['post', 'payments.confirm', $payment], ['post', 'payments.reject', $payment], ['post', 'payments.void', $payment],
        ['get', 'charges.edit', $charge], ['put', 'charges.update', $charge],
        ['get', 'charges.generate.create', []], ['post', 'charges.generate', []],
        ['get', 'fee-types.create', []], ['post', 'fee-types.store', []],
        ['get', 'fee-types.edit', $feeType], ['put', 'fee-types.update', $feeType],
        ['put', 'levy-settings.update', []], ['put', 'bill-settings.update', []],
        ['get', 'printed-bills.show', $charge->property], ['get', 'printed-bills.batch', []],
    ] as [$method, $route, $parameters]) {
        $this->actingAs($officer)->{$method}(route('officer.'.$route, $parameters))->assertForbidden();
    }
    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
    $this->assertDatabaseCount('payment_allocations', 0);
});

test('Payment confirmation can allocate debt without a listing grant and still rejects invalid states', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['payments.confirm']);
    $property = Property::factory()->create(['opening_balance' => '100.00']);
    $payment = Payment::factory()->for($property)->create(['amount' => '150.00']);
    $this->actingAs($officer)->get(route('officer.payments.index'))->assertForbidden();
    $this->post(route('officer.payments.confirm', $payment))->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Confirmed)
        ->and($property->fresh()->prepaid_balance)->toBe('50.00');
    $this->assertDatabaseHas('payment_allocations', ['payment_id' => $payment->id, 'amount' => '100.00']);
    $this->post(route('officer.payments.confirm', $payment))->assertSessionHasErrors('payment');
    $this->post(route('officer.payments.void', $payment), ['void_reason' => 'Correction'])->assertForbidden();
});

test('Payment entry is its own combined record and confirm operation without listing or declaration-confirm access', function () {
    Storage::fake('local');
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['payments.create']);
    $property = Property::factory()->create(['opening_balance' => '100.00']);
    $this->actingAs($officer)->get(route('officer.payments.create'))->assertOk();
    $this->post(route('officer.payments.store'), [
        'property_id' => $property->id, 'amount' => '100.00', 'method' => 'cash',
        'screenshot' => UploadedFile::fake()->image('receipt.jpg'),
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
    $payment = Payment::query()->where('property_id', $property->id)->sole();
    expect($payment->status)->toBe(PaymentStatus::Confirmed);
    $this->assertDatabaseHas('payment_allocations', ['payment_id' => $payment->id, 'amount' => '100.00']);
    $pending = Payment::factory()->create();
    $this->post(route('officer.payments.confirm', $pending))->assertForbidden();
});

test('rejection and voiding grants retain required reasons and Payment state rules', function (string $grant, string $route, string $reasonField, bool $confirmed) {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions([$grant]);
    $payment = Payment::factory()->create();
    if ($confirmed) {
        app(ConfirmPayment::class)->handle($payment, $officer);
    }
    $this->actingAs($officer)->post(route($route, $payment))->assertSessionHasErrors($reasonField);
    $this->post(route($route, $payment), [$reasonField => 'Receipt correction'])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
    expect($payment->fresh()->status)->toBe($confirmed ? PaymentStatus::Voided : PaymentStatus::Rejected);
    $this->post(route($route, $payment), [$reasonField => 'Repeated correction'])->assertSessionHasErrors('payment');
})->with([
    'reject' => ['payments.reject', 'officer.payments.reject', 'rejection_reason', false],
    'void' => ['payments.void', 'officer.payments.void', 'void_reason', true],
]);

test('Fee Type creation and Charge generation work without their list grants', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['fee-types.create', 'charges.generate']);
    $this->actingAs($officer)->get(route('officer.fee-types.create'))->assertOk();
    $this->post(route('officer.fee-types.store'), ['name' => 'Monthly guard', 'amount' => '100.00'])
        ->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('fee_types', ['name' => 'Monthly guard', 'amount' => '100.00']);
    $this->get(route('officer.fee-types.index'))->assertForbidden();
    $this->get(route('officer.charges.generate.create'))->assertOk();
    $this->post(route('officer.charges.generate'), ['year' => 2026, 'month' => 10])->assertSessionHasNoErrors()->assertRedirect(route('officer.charges.generate.create'));
    $this->assertDatabaseHas('charges', ['year' => 2026, 'month' => 10]);
    $this->get(route('officer.charges.index'))->assertForbidden();
});

test('single and batch Printed Bill downloads require their own grant without unpaid reading', function (string $grant, string $allowed, string $denied) {
    Pdf::fake();
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions([$grant]);
    $property = Property::factory()->create(['opening_balance' => '100.00']);
    $parameters = ['property' => $property, 'year' => 2026, 'month' => 10];
    $this->actingAs($officer)->get(route($allowed, $parameters))->assertOk();
    Pdf::assertRespondedWithPdf(fn ($pdf): bool => $pdf->isDownload());
    $this->get(route($denied, $parameters))->assertForbidden();
    $this->get(route('officer.unpaid.index'))->assertForbidden();
})->with([
    'single' => ['printed-bills.single', 'officer.printed-bills.show', 'officer.printed-bills.batch'],
    'batch' => ['printed-bills.batch', 'officer.printed-bills.batch', 'officer.printed-bills.show'],
]);

test('billing settings have independent read and edit grants', function (string $section, string $readGrant, string $editGrant, array $payload) {
    $officer = User::factory()->officer()->create();
    $role = Role::findByName('officer', 'web');
    $role->syncPermissions([$readGrant]);
    $this->actingAs($officer)->get(route('officer.'.$section.'.edit'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('canEdit', false));
    $this->put(route('officer.'.$section.'.update'), $payload)->assertForbidden();
    $role->syncPermissions([$editGrant]);
    $this->get(route('officer.'.$section.'.edit'))->assertOk()->assertInertia(fn ($page) => $page->where('canEdit', true));
    $this->put(route('officer.'.$section.'.update'), $payload)->assertSessionHasNoErrors()->assertRedirect(route('officer.'.$section.'.edit'));
})->with([
    'levy' => ['levy-settings', 'levy-settings.read', 'levy-settings.edit', ['levy_day_of_month' => 18]],
    'bill' => ['bill-settings', 'bill-settings.read', 'bill-settings.edit', [
        'letterhead_name' => 'Community', 'letterhead_short_name' => 'BSR', 'letterhead_address_lines' => ['Basak'],
        'letterhead_contact' => 'Office', 'letterhead_treasurer' => 'Treasurer', 'payment_channels' => [['method' => 'Cash', 'detail' => 'HOA office']],
    ]],
]);

test('financial grants are role-specific audited and revoked on the next request in the same session', function () {
    $actor = User::factory()->superAdmin()->create();
    $administrator = User::factory()->administrator()->create();
    $officerRole = Role::findByName('officer', 'web');
    $adminRole = Role::findByName('administrator', 'web');
    $adminRole->syncPermissions(['payments.read']);
    $officerRole->syncPermissions(['payments.confirm']);
    $this->actingAs($administrator)->get(route('officer.payments.index'))->assertOk();
    $payment = Payment::factory()->create();
    $this->post(route('officer.payments.confirm', $payment))->assertForbidden();
    $this->actingAs($actor)->put(route('super-admin.role-permissions.update', $adminRole), ['grants' => []])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('role_permission_changes', ['role_id' => $adminRole->id, 'actor_user_id' => $actor->id]);
    $this->actingAs($administrator)->get(route('officer.payments.index'))->assertForbidden();
    $administrator->givePermissionTo('payments.read');
    $this->get(route('officer.payments.index'))->assertForbidden();
    $this->actingAs($actor)->get(route('officer.payments.index'))->assertOk();
    $this->get(route('super-admin.role-permissions.index'))->assertOk()->assertInertia(fn ($page) => $page->has('permissions', 49));
});

test('Charge editors can edit without listing but cannot rewrite frozen financial history', function (bool $frozen) {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['charges.edit']);
    $charge = Charge::factory()->create(['frozen_at' => $frozen ? now() : null]);
    $line = ChargeLine::factory()->for($charge)->create(['amount' => '100.00']);
    $this->actingAs($officer)->get(route('officer.charges.edit', $charge))->assertOk();
    $this->get(route('officer.charges.index'))->assertForbidden();
    $response = $this->put(route('officer.charges.update', $charge), ['lines' => [[
        'id' => $line->id, 'fee_type_id' => $line->fee_type_id,
        'fee_type_name' => $line->fee_type_name, 'amount' => '50.00',
    ]]])->assertRedirect(route('officer.charges.edit', $charge));
    if ($frozen) {
        $response->assertSessionHas('error');
    } else {
        $response->assertSessionHas('success');
    }
    expect($line->fresh()->amount)->toBe($frozen ? '100.00' : '50.00');
})->with(['unfrozen' => false, 'frozen' => true]);

test('Member declaration and own Statement access remain Property-scoped without financial staff grants', function () {
    Storage::fake('local');
    $member = User::factory()->member()->create();
    $ownProperty = $member->memberships()->live()->sole()->property;
    $otherProperty = Property::factory()->create();
    $this->actingAs($member)->get(route('statement-of-account.show', $ownProperty))->assertOk();
    $this->get(route('statement-of-account.show', $otherProperty))->assertForbidden();
    $payload = ['property_id' => $ownProperty->id, 'amount' => '100.00', 'method' => 'cash', 'screenshot' => UploadedFile::fake()->image('receipt.jpg')];
    $this->post(route('payments.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
    expect(Payment::query()->where('property_id', $ownProperty->id)->sole()->status)->toBe(PaymentStatus::Pending);
    $this->post(route('payments.store'), [...$payload, 'property_id' => $otherProperty->id])->assertForbidden();
    $this->get(route('officer.payments.index'))->assertForbidden();
    $this->get(route('officer.printed-bills.show', ['property' => $ownProperty, 'year' => 2026, 'month' => 10]))->assertForbidden();
});

test('Super Admin can reach every financial screen and download while staff defaults survive reseeding', function () {
    Pdf::fake();
    $actor = User::factory()->superAdmin()->create();
    $charge = Charge::factory()->create();
    $feeType = FeeType::factory()->create();
    $this->actingAs($actor);
    foreach ([
        ['payments.index', []], ['payments.create', []], ['charges.index', []], ['charges.edit', $charge], ['charges.generate.create', []],
        ['unpaid.index', []], ['fee-types.index', []], ['fee-types.create', []], ['fee-types.edit', $feeType],
        ['levy-settings.edit', []], ['bill-settings.edit', []],
        ['printed-bills.show', ['property' => $charge->property, 'year' => 2026, 'month' => 10]],
        ['printed-bills.batch', ['year' => 2026, 'month' => 10]],
    ] as [$name, $parameters]) {
        $this->get(route('officer.'.$name, $parameters))->assertOk();
    }
    $role = Role::findByName('officer', 'web');
    expect($role->permissions)->toHaveCount(43);
    $role->syncPermissions(['payments.read']);
    $this->seed(RoleSeeder::class);
    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['payments.read']);
});

test('each financial viewing grant opens its own screen without unrelated access', function (string $grant, string $screen) {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions([$grant]);
    $this->actingAs($officer)->get(route($screen))->assertOk();
    $this->get(route('officer.payments.create'))->assertForbidden();
    $this->get(route('officer.properties.index'))->assertForbidden();
})->with([
    'Payments' => ['payments.read', 'officer.payments.index'],
    'Charges' => ['charges.read', 'officer.charges.index'],
    'Unpaid' => ['unpaid.read', 'officer.unpaid.index'],
    'Fee Types' => ['fee-types.read', 'officer.fee-types.index'],
    'Levy settings' => ['levy-settings.read', 'officer.levy-settings.edit'],
    'Bill settings' => ['bill-settings.read', 'officer.bill-settings.edit'],
]);

test('Fee Type editing without listing updates the fee and returns to an accessible workspace', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['fee-types.edit']);
    $feeType = FeeType::factory()->create(['amount' => '100.00']);
    $this->actingAs($officer)->get(route('officer.fee-types.edit', $feeType))->assertOk();
    $this->put(route('officer.fee-types.update', $feeType), ['name' => $feeType->name, 'amount' => '150.00'])
        ->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
    expect($feeType->fresh()->amount)->toBe('150.00');
    $this->get(route('officer.fee-types.index'))->assertForbidden();
});

test('financial onboarding shortcuts require their viewing grant', function (string $step) {
    $officer = User::factory()->officer()->create();
    $progress = OnboardingProgress::factory()->for($officer)->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions([]);
    $this->actingAs($officer)->post(route('onboarding.steps.store', 'officer'), ['step' => $step])->assertForbidden();
    expect($progress->fresh()->completed_steps)->toBe([]);
})->with(['officer-payments', 'officer-charges']);
