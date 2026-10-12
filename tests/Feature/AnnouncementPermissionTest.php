<?php

use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\OnboardingProgress;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('Officer cannot read the staff Announcement list without its role grant', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions([]);

    $this->actingAs($officer)
        ->get(route('officer.announcements.index'))
        ->assertForbidden();
});

test('creation works independently of staff reading and redirects to an accessible page', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['announcements.create']);

    $this->actingAs($officer)->post(route('officer.announcements.store'), [
        'title' => 'Community cleanup',
        'body' => '<p>Saturday morning.</p>',
        'visibility' => 'public',
    ])->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('announcements', ['title' => 'Community cleanup']);
});

test('removing the read grant removes privileged Hidden article access on the next request', function () {
    $officer = User::factory()->officer()->create();
    $announcement = Announcement::factory()->published()->create(['visibility' => 'hidden']);
    $this->actingAs($officer)->get(route('announcements.show', $announcement))->assertOk();

    Role::findByName('officer', 'web')->revokePermissionTo('announcements.read');

    $this->get(route('announcements.show', $announcement))->assertNotFound();
});

test('a reader can open a draft without receiving edit or upload permission', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['announcements.read']);
    $draft = Announcement::factory()->create();

    $this->actingAs($officer)->get(route('officer.announcements.edit', $draft))
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('officer/announcements/Edit')
        ->where('auth.announcementPermissions', ['announcements.read']));
    $this->put(route('officer.announcements.update', $draft), [])->assertForbidden();
});

test('reseeding the catalog preserves edited grants and creates missing permissions without granting them', function () {
    $role = Role::findByName('officer', 'web');
    $role->syncPermissions(['announcements.read']);
    Permission::findByName('announcements.pin', 'web')->delete();

    $this->seed(RoleSeeder::class);

    $this->assertDatabaseHas('permissions', ['name' => 'announcements.pin', 'guard_name' => 'web']);
    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['announcements.read']);
});

test('reading grants never authorize direct mutation requests', function (string $action, string $method) {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['announcements.read']);
    $announcement = Announcement::factory()->published()->create(['title' => 'Original title']);

    $this->actingAs($officer)->{$method}(route('officer.announcements.'.$action, $announcement), [])
        ->assertForbidden();
    expect($announcement->fresh()->title)->toBe('Original title');
    $this->assertDatabaseCount('announcement_attachments', 0);
})->with([
    ['store', 'post'], ['update', 'put'], ['publish', 'post'], ['unpublish', 'post'],
    ['pin', 'post'], ['unpin', 'post'], ['attachments.store', 'post'],
]);

test('Administrator does not inherit Officer grants and direct or automatic account grants are ignored', function () {
    $administrator = User::factory()->administrator()->create();
    Role::findByName('administrator', 'web')->syncPermissions([]);
    $administrator->givePermissionTo('announcements.read');
    $this->actingAs($administrator)->get(route('officer.announcements.index'))->assertForbidden();

    $member = User::factory()->member()->create();
    Role::findByName('member', 'web')->givePermissionTo('announcements.read');
    $this->actingAs($member)->get(route('officer.announcements.index'))->assertForbidden();

    $this->actingAs($administrator)->get(route('officer.properties.index'))->assertForbidden();
    $this->get(route('officer.fee-types.index'))->assertOk();
    $this->get(route('officer.payments.index'))->assertOk();
});

test('staff must retain a live owner Membership while Super Admin always has all grants', function () {
    $officer = User::factory()->officer()->create();
    $officer->memberships()->update(['ended_at' => now()]);
    $this->actingAs($officer)->get(route('officer.announcements.index'))->assertForbidden();

    Role::findByName('officer', 'web')->syncPermissions([]);
    Role::findByName('administrator', 'web')->syncPermissions([]);
    $this->actingAs(User::factory()->superAdmin()->create())->get(route('officer.announcements.index'))
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->has('auth.announcementPermissions', 6));
});

test('attachment reading follows current audience and the explicit staff reading grant', function (string $visibility, bool $published) {
    Storage::fake('local');
    $officer = User::factory()->officer()->create();
    $announcement = Announcement::factory()->create([
        'visibility' => $visibility, 'published_at' => $published ? now() : null,
    ]);
    $attachment = AnnouncementAttachment::factory()->for($announcement)->create();
    Storage::disk('local')->put($attachment->path, 'test document');

    $this->actingAs($officer)->get(route('announcements.attachments.show', $attachment))->assertOk();
    Role::findByName('officer', 'web')->revokePermissionTo('announcements.read');
    $response = $this->get(route('announcements.attachments.show', $attachment));
    if ($visibility === 'private' && $published) {
        $response->assertOk();
    } else {
        $response->assertNotFound();
    }
})->with([['hidden', true], ['private', true], ['private', false]]);

test('each mutation grant works without the read grant', function (string $permission, string $action, string $method, array $data) {
    Storage::fake('local');
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions([$permission]);
    $announcement = Announcement::factory()->create([
        'published_at' => $action === 'publish' ? null : now(),
        'pinned_at' => $action === 'unpin' ? now() : null,
    ]);
    if ($action === 'attachments.store') {
        $data = ['attachments' => [UploadedFile::fake()->image('flyer.png')]];
    }
    $this->actingAs($officer)->{$method}(route('officer.announcements.'.$action, $announcement), $data)
        ->assertRedirect()->assertSessionHasNoErrors();
    $announcement->refresh();
    match ($action) {
        'update' => expect($announcement->title)->toBe('Updated notice'),
        'publish' => expect($announcement->published_at)->not->toBeNull(),
        'unpublish' => expect($announcement->published_at)->toBeNull(),
        'pin' => expect($announcement->pinned_at)->not->toBeNull(),
        'unpin' => expect($announcement->pinned_at)->toBeNull(),
        'attachments.store' => expect($announcement->attachments)->toHaveCount(1),
    };
})->with([
    ['announcements.edit', 'update', 'put', ['title' => 'Updated notice', 'body' => '<p>Updated.</p>', 'visibility' => 'hidden']],
    ['announcements.publish', 'publish', 'post', []],
    ['announcements.publish', 'unpublish', 'post', []],
    ['announcements.pin', 'pin', 'post', []],
    ['announcements.pin', 'unpin', 'post', []],
    ['announcements.attachments', 'attachments.store', 'post', []],
]);

test('editor changes revoke a cached signed in Officers access and update shared grants on the next request', function () {
    $officer = User::factory()->officer()->create();
    expect($officer->hasPermissionTo('announcements.read'))->toBeTrue();
    $officer->load('roles.permissions');
    $this->actingAs($officer)->get(route('officer.announcements.index'))->assertOk();

    $this->actingAs(User::factory()->superAdmin()->create())
        ->put(route('super-admin.role-permissions.update', Role::findByName('officer', 'web')),
            ['grants' => ['announcements.create']])->assertRedirect();

    $this->actingAs($officer)->get(route('officer.announcements.index'))->assertForbidden();
    $this->get(route('dashboard'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('auth.announcementPermissions', ['announcements.create'])
        ->where('auth.capabilities.canAccessOfficer', true));
});

test('attachment permission alone does not expose a Hidden article through the editing page', function () {
    $officer = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['announcements.attachments']);
    $announcement = Announcement::factory()->published()->create(['visibility' => 'hidden']);

    $this->actingAs($officer)->get(route('officer.announcements.edit', $announcement))->assertForbidden();
    $this->get(route('announcements.show', $announcement))->assertNotFound();
});

test('Announcement onboarding navigation cannot mark a denied staff visit complete', function () {
    $officer = User::factory()->officer()->create();
    $progress = OnboardingProgress::factory()->for($officer)->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['announcements.create']);

    $this->actingAs($officer)->post(route('onboarding.steps.store', 'officer'), ['step' => 'officer-announcements'])
        ->assertForbidden();
    expect($progress->fresh()->completed_steps)->toBe([]);
});
