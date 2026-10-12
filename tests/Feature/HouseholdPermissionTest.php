<?php

use App\Actions\PropertyInvitations\CreatePropertyInvitation;
use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\OnboardingProgress;
use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Models\Suspend;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

test('staff Property reading requires an explicit role grant', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['announcements.read']);
    $this->actingAs($officer)->get(route('officer.properties.index'))->assertForbidden();
});

test('the role editor exposes the migrated household grants without changing Announcement grants', function () {
    $actor = User::factory()->superAdmin()->create();
    $role = Role::findByName('officer', 'web');
    $grants = ['announcements.read', 'properties.read', 'invitations.credentials'];
    $this->actingAs($actor)->put(route('super-admin.role-permissions.update', $role), ['grants' => $grants])
        ->assertSessionHasNoErrors()->assertRedirect();
    expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe(['announcements.read', 'invitations.credentials', 'properties.read']);
    $this->get(route('super-admin.role-permissions.index'))->assertInertia(fn ($page) => $page
        ->has('permissions', 26)->where('permissions.6.feature', 'Properties'));
});

test('Property creation works without the listing grant and returns to an accessible workspace', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['properties.create']);
    $this->actingAs($officer)->post(route('officer.properties.store'), ['block' => 'New', 'lot' => '1'])
        ->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('properties', ['block' => 'New', 'lot' => '1']);
});

test('creation-only Invitation users can open the creation screen without reading other Invitations', function () {
    $officer = User::factory()->officer()->create();
    PropertyInvitation::factory()->create();
    Role::findByName('officer', 'web')->syncPermissions(['invitations.create']);
    $this->actingAs($officer)->get(route('officer.property-invitations.create'))->assertOk()
        ->assertInertia(fn ($page) => $page->has('invitations', 0)->has('properties'));
    $this->get(route('officer.property-invitations.index'))->assertForbidden();
});

test('staff Profile reading does not authorize editing and own Property self service remains available', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create();
    Role::findByName('officer', 'web')->syncPermissions(['properties.profiles.read']);
    $this->actingAs($officer)->get(route('property-profile.edit', $property))->assertOk()
        ->assertInertia(fn ($page) => $page->where('canEditProfile', false));
    $this->put(route('property-profile.update', $property), [])->assertNotFound();
    $ownProperty = $officer->memberships()->live()->sole()->property;
    $this->get(route('property-profile.edit', $ownProperty))->assertOk()
        ->assertInertia(fn ($page) => $page->where('canEditProfile', true));
});

test('read grants do not authorize household mutation or credential endpoints', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions([
        'properties.read', 'invitations.read', 'memberships.read', 'suspends.read',
    ]);
    $property = Property::factory()->create();
    $invitation = PropertyInvitation::factory()->create();
    $membership = Membership::factory()->owner()->create();
    $suspend = Suspend::factory()->create();
    foreach ([
        ['get', 'properties.create', []], ['post', 'properties.store', []],
        ['get', 'properties.edit', $property], ['put', 'properties.update', $property],
        ['delete', 'properties.destroy', $property], ['post', 'properties.deactivate', $property],
        ['post', 'properties.activate', $property], ['get', 'properties.import.create', []],
        ['get', 'properties.import.sample', []], ['post', 'properties.import.store', []],
        ['post', 'property-invitations.store', []], ['delete', 'property-invitations.destroy', $invitation],
        ['get', 'property-invitations.share', $invitation], ['put', 'memberships.update-role', $membership],
        ['post', 'memberships.end', $membership], ['get', 'suspends.create', []],
        ['post', 'suspends.store', []], ['get', 'suspends.edit', $suspend], ['put', 'suspends.update', $suspend],
    ] as [$method, $route, $parameters]) {
        $this->actingAs($officer)->{$method}(route('officer.'.$route, $parameters))->assertForbidden();
    }
    expect($property->fresh()->is_active)->toBeTrue();
    expect($invitation->fresh()->revoked_at)->toBeNull();
    expect($membership->fresh()->ended_at)->toBeNull();
    $this->assertDatabaseCount('membership_changes', 0);
});

test('credential retrieval is independent of listing and continues to reject consumed Invitations', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['invitations.credentials']);
    $issued = app(CreatePropertyInvitation::class)->handle(
        Property::factory()->create(), MembershipRole::Resident, $officer,
    );
    $invitation = PropertyInvitation::query()->where('code_hash', hash('sha256', $issued->code))->sole();
    $this->actingAs($officer)->getJson(route('officer.property-invitations.share', $invitation))
        ->assertOk()->assertJsonPath('code', $issued->code)->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('officer.property-invitations.index'))->assertForbidden();
    $invitation->update(['consumed_at' => now()]);
    $this->getJson(route('officer.property-invitations.share', $invitation))
        ->assertUnprocessable()->assertJsonMissing(['code' => $issued->code]);
});

test('import grants preserve the default Super Admin restriction and can be granted independently', function () {
    $officer = User::factory()->officer()->create();
    $role = Role::findByName('officer', 'web');
    expect($role->hasPermissionTo('properties.import'))->toBeFalse();
    $this->actingAs($officer)->post(route('officer.properties.import.store'), [])->assertForbidden();
    $role->syncPermissions(['properties.import']);
    $file = UploadedFile::fake()->createWithContent('roster.csv', "block,lot\nImported,1\n");
    $this->post(route('officer.properties.import.store'), ['csv' => $file])->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('properties', ['block' => 'Imported', 'lot' => '1']);
    $this->get(route('officer.properties.import.sample'))->assertForbidden();
});

test('household grants apply to the association and revoked grants take effect on the next request', function () {
    $officer = User::factory()->officer()->create();
    $otherProperty = Property::factory()->create(['recorded_owner_name' => 'Other Owner']);
    $role = Role::findByName('officer', 'web');
    $role->syncPermissions(['properties.edit']);
    $this->actingAs($officer)->put(route('officer.properties.update', $otherProperty), ['street_address' => 'Changed'])
        ->assertRedirect(route('dashboard'));
    expect($otherProperty->fresh()->street_address)->toBe('Changed');
    $role->syncPermissions([]);
    $this->put(route('officer.properties.update', $otherProperty), ['street_address' => 'Denied'])->assertForbidden();
    expect($otherProperty->fresh()->street_address)->toBe('Changed');
    $this->get(route('officer.payments.index'))->assertOk();
});

test('a delete grant cannot delete charged Property history', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['properties.delete']);
    $property = Property::factory()->charged()->create();
    $this->actingAs($officer)->delete(route('officer.properties.destroy', $property))
        ->assertRedirect(route('dashboard'))->assertSessionHas('error');
    $this->assertDatabaseHas('properties', ['id' => $property->id]);
});

test('seeded grants retain staff access without broad role or direct grant bypasses', function () {
    $officer = User::factory()->officer()->create();
    $administrator = User::factory()->administrator()->create();
    $role = Role::findByName('officer', 'web');
    expect($role->permissions)->toHaveCount(25);
    expect($role->hasPermissionTo('properties.read'))->toBeTrue();
    Role::findByName('administrator', 'web')->syncPermissions([]);
    $administrator->givePermissionTo('properties.read');
    $this->actingAs($administrator)->get(route('officer.properties.index'))->assertForbidden();
    $this->actingAs($officer)->get(route('officer.properties.index'))->assertOk();
    $role->syncPermissions([]);
    $this->seed(RoleSeeder::class);
    $this->get(route('officer.properties.index'))->assertForbidden();
    expect($role->fresh()->permissions)->toHaveCount(0);
    $this->actingAs(User::factory()->superAdmin()->create())->get(route('officer.properties.index'))->assertOk();
});

test('household onboarding links require their reading grant', function (string $step) {
    $officer = User::factory()->officer()->create();
    $progress = OnboardingProgress::factory()->for($officer)->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions([]);
    $this->actingAs($officer)->post(route('onboarding.steps.store', 'officer'), ['step' => $step])->assertForbidden();
    expect($progress->fresh()->completed_steps)->toBe([]);
})->with(['officer-properties', 'officer-invitations', 'officer-memberships']);
