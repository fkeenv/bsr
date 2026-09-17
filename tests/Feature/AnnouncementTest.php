<?php

use App\Enums\PlatformRole;
use App\Models\Announcement;
use App\Models\Membership;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('Officer can create a shared Announcement draft', function () {
    $officer = User::factory()->officer()->create();

    $this->actingAs($officer)
        ->post(route('officer.announcements.store'), [
            'title' => 'Pool maintenance',
            'body' => '<p>The pool will close Friday.</p>',
        ])
        ->assertRedirect(route('officer.announcements.index'));

    $this->assertDatabaseHas('announcements', [
        'title' => 'Pool maintenance',
        'created_by_user_id' => $officer->id,
        'published_at' => null,
        'pinned_at' => null,
    ]);
});

test('another Officer can edit a shared draft', function () {
    $author = User::factory()->officer()->create();
    $editor = User::factory()->officer()->create();
    $announcement = Announcement::factory()->draft()->create([
        'title' => 'Old title',
        'body' => '<p>Old body</p>',
        'created_by_user_id' => $author->id,
    ]);

    $this->actingAs($editor)
        ->put(route('officer.announcements.update', $announcement), [
            'title' => 'Updated title',
            'body' => '<p>Updated body</p>',
        ])
        ->assertRedirect(route('officer.announcements.index'));

    $announcement->refresh();

    expect($announcement->title)->toBe('Updated title')
        ->and($announcement->body)->toBe('<p>Updated body</p>')
        ->and($announcement->updated_by_user_id)->toBe($editor->id);
});

test('Officer can publish and unpublish an Announcement back to drafts', function () {
    $officer = User::factory()->officer()->create();
    $announcement = Announcement::factory()->draft()->create([
        'created_by_user_id' => $officer->id,
    ]);

    $this->actingAs($officer)
        ->post(route('officer.announcements.publish', $announcement))
        ->assertRedirect();

    $announcement->refresh();
    expect($announcement->published_at)->not->toBeNull();

    $this->actingAs($officer)
        ->post(route('officer.announcements.unpublish', $announcement))
        ->assertRedirect();

    $announcement->refresh();
    expect($announcement->published_at)->toBeNull()
        ->and($announcement->pinned_at)->toBeNull();
});

test('at most three published Announcements can be pinned', function () {
    $officer = User::factory()->officer()->create();
    $pinned = Announcement::factory()->published()->count(3)->create([
        'created_by_user_id' => $officer->id,
        'pinned_at' => now(),
    ]);
    $fourth = Announcement::factory()->published()->create([
        'created_by_user_id' => $officer->id,
    ]);

    $this->actingAs($officer)
        ->from(route('officer.announcements.index'))
        ->post(route('officer.announcements.pin', $fourth))
        ->assertRedirect(route('officer.announcements.index'))
        ->assertSessionHasErrors('pin');

    expect($fourth->fresh()->pinned_at)->toBeNull();
    expect($pinned->every(fn (Announcement $item): bool => $item->fresh()->pinned_at !== null))->toBeTrue();
});

test('Officer can attach image and PDF files to an Announcement', function () {
    Storage::fake('local');
    $officer = User::factory()->officer()->create();
    $announcement = Announcement::factory()->published()->create([
        'created_by_user_id' => $officer->id,
    ]);

    $this->actingAs($officer)
        ->post(route('officer.announcements.attachments.store', $announcement), [
            'attachments' => [
                UploadedFile::fake()->image('notice.jpg'),
                UploadedFile::fake()->create('rules.pdf', 100, 'application/pdf'),
            ],
        ])
        ->assertRedirect();

    $announcement->refresh()->load('attachments');

    expect($announcement->attachments)->toHaveCount(2);
    foreach ($announcement->attachments as $attachment) {
        Storage::disk('local')->assertExists($attachment->path);
    }
});

test('Member with a live Membership sees published feed with pins first and can search title and body', function () {
    $member = User::factory()->create();
    $property = Property::factory()->create();
    Membership::factory()->owner()->create([
        'user_id' => $member->id,
        'property_id' => $property->id,
    ]);
    $member->assignPlatformRole(PlatformRole::Member);

    $officer = User::factory()->officer()->create();

    $pinned = Announcement::factory()->published()->create([
        'title' => 'Pinned notice',
        'body' => '<p>Urgent pool closure</p>',
        'created_by_user_id' => $officer->id,
        'published_at' => now()->subDay(),
        'pinned_at' => now(),
    ]);
    $newer = Announcement::factory()->published()->create([
        'title' => 'Gate schedule',
        'body' => '<p>New hours</p>',
        'created_by_user_id' => $officer->id,
        'published_at' => now(),
    ]);
    Announcement::factory()->draft()->create([
        'title' => 'Secret draft',
        'body' => '<p>Not ready</p>',
        'created_by_user_id' => $officer->id,
    ]);
    Announcement::factory()->published()->create([
        'title' => 'Other topic',
        'body' => '<p>Unrelated</p>',
        'created_by_user_id' => $officer->id,
        'published_at' => now()->subHours(2),
    ]);

    $this->actingAs($member)
        ->get(route('announcements.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('announcements/Index')
            ->has('announcements', 3)
            ->where('announcements.0.id', $pinned->id)
            ->where('announcements.1.id', $newer->id)
            ->missing('announcements.3')
        );

    $this->actingAs($member)
        ->get(route('announcements.index', ['search' => 'pool']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('announcements/Index')
            ->has('announcements', 1)
            ->where('announcements.0.id', $pinned->id)
        );

    $this->actingAs($member)
        ->get(route('announcements.index', ['search' => 'Secret']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('announcements/Index')
            ->has('announcements', 0)
        );
});

test('published Announcement attachments are available on the feed show page', function () {
    Storage::fake('local');
    $member = User::factory()->create();
    $property = Property::factory()->create();
    Membership::factory()->resident()->create([
        'user_id' => $member->id,
        'property_id' => $property->id,
    ]);
    $member->assignPlatformRole(PlatformRole::Member);

    $officer = User::factory()->officer()->create();
    $announcement = Announcement::factory()->published()->create([
        'created_by_user_id' => $officer->id,
    ]);

    $path = UploadedFile::fake()->image('flyer.png')->store('announcement-attachments', 'local');
    $attachment = $announcement->attachments()->create([
        'path' => $path,
        'original_filename' => 'flyer.png',
        'mime_type' => 'image/png',
        'disk' => 'local',
        'size' => 1024,
    ]);

    $this->actingAs($member)
        ->get(route('announcements.show', $announcement))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('announcements/Show')
            ->where('announcement.id', $announcement->id)
            ->has('announcement.attachments', 1)
            ->where('announcement.attachments.0.id', $attachment->id)
            ->where('announcement.attachments.0.original_filename', 'flyer.png')
        );

    $this->actingAs($member)
        ->get(route('announcements.attachments.show', $attachment))
        ->assertOk();
});

test('User Account without a live Membership cannot read the Announcement feed', function () {
    $user = User::factory()->create();
    Announcement::factory()->published()->create();

    $this->actingAs($user)
        ->get(route('announcements.index'))
        ->assertForbidden();
});

test('Super Admin without a live Membership can read the Announcement feed', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $announcement = Announcement::factory()->published()->create([
        'title' => 'Association notice',
    ]);

    $this->actingAs($superAdmin)
        ->get(route('announcements.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('announcements/Index')
            ->has('announcements', 1)
            ->where('announcements.0.id', $announcement->id)
        );
});

test('plain User Account cannot manage Officer Announcements', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('officer.announcements.index'))
        ->assertRedirect(route('membership-application.create'));
});
