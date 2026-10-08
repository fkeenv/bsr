<?php

use App\Enums\PlatformRole;
use App\Models\Announcement;
use App\Models\AssociationSetting;
use App\Models\Membership;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('Officer workspace exposes readable excerpts and the actual notice dates', function () {
    $officer = User::factory()->officer()->create();
    $body = '<h2>Gate hours</h2><p>Open &amp; welcoming&nbsp;daily.</p><script>alert("bad")</script><style>hidden</style><p>Bring your pass.</p>';
    $announcement = Announcement::factory()->published()->create([
        'body' => $body,
        'published_at' => '2026-10-07 23:30:00',
        'updated_at' => '2026-10-08 02:00:00',
    ]);

    $this->actingAs($officer)->get(route('officer.announcements.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('officer/announcements/Index')
            ->where('announcements.0.id', $announcement->id)
            ->where('announcements.0.excerpt', 'Gate hours Open & welcoming daily. Bring your pass.')
            ->where('announcements.0.body', $body)
            ->where('announcements.0.published_at', '2026-10-07T23:30:00+00:00')
            ->where('announcements.0.updated_at', '2026-10-08T02:00:00+00:00')
        );
});

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

test('User Account without a live Membership receives only public notices', function () {
    $user = User::factory()->create();
    Announcement::factory()->published()->create();

    $this->actingAs($user)
        ->get(route('announcements.index'))
        ->assertOk()->assertInertia(fn ($page) => $page->has('announcements', 0));
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
        ->assertForbidden();
});

test('Officer create and edit pages use the rich text Announcement form', function () {
    $officer = User::factory()->officer()->create();
    $announcement = Announcement::factory()->draft()->create([
        'created_by_user_id' => $officer->id,
    ]);

    $this->actingAs($officer)
        ->get(route('officer.announcements.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('officer/announcements/Create'));

    $this->actingAs($officer)
        ->get(route('officer.announcements.edit', $announcement))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('officer/announcements/Edit')
            ->where('announcement.id', $announcement->id)
        );
});

test('new drafts default to Private and the global visibility endpoint is removed', function () {
    $officer = User::factory()->officer()->create();
    $this->actingAs($officer)->post(route('officer.announcements.store'), [
        'title' => 'Default privacy', 'body' => '<p>Members only.</p>',
    ])->assertRedirect();
    $this->get(route('officer.announcements.index'))->assertInertia(fn ($page) => $page
        ->where('announcements.0.visibility', 'private')->missing('pageVisibility')->missing('pageVisibilityOptions'));
    $this->put('/officer/announcements-page-visibility', ['announcements_page_visibility' => 'public'])->assertNotFound();
});

test('guests can read public Announcements', function () {
    $announcement = Announcement::factory()->published()->create([
        'title' => 'Open notice',
        'visibility' => 'public',
    ]);

    $this->get(route('announcements.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('announcements/Index')
            ->where('isPublicVisitor', true)
            ->has('announcements', 1)
            ->where('announcements.0.id', $announcement->id)
        );
});

test('Members cannot read hidden Announcements', function () {
    $member = User::factory()->create();
    $property = Property::factory()->create();
    Membership::factory()->owner()->create([
        'user_id' => $member->id,
        'property_id' => $property->id,
    ]);
    $member->assignPlatformRole(PlatformRole::Member);

    $announcement = Announcement::factory()->published()->create(['visibility' => 'hidden']);

    $this->actingAs($member)
        ->get(route('announcements.show', $announcement))
        ->assertNotFound();
});

test('Officer excerpts keep encoded text and shorten long notices', function () {
    $officer = User::factory()->officer()->create();
    Announcement::factory()->draft()->create([
        'body' => '<p>&lt;Gate&gt; &quot;Pass&quot; &#39;Resident&#39;</p>',
    ]);

    $this->actingAs($officer)->get(route('officer.announcements.index'))
        ->assertInertia(fn ($page) => $page->where('announcements.0.excerpt', '<Gate> "Pass" \'Resident\''));

    Announcement::query()->delete();
    Announcement::factory()->draft()->create(['body' => '<p>'.str_repeat('á', 200).'</p>']);

    $this->get(route('officer.announcements.index'))
        ->assertInertia(fn ($page) => $page->where('announcements.0.excerpt', str_repeat('á', 180).'...'));
});

test('a plain User Account cannot change an Announcement visibility', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->draft()->create();
    $this->actingAs($user)->put(route('officer.announcements.update', $announcement), [
        'title' => 'Changed', 'body' => '<p>Changed.</p>', 'visibility' => 'public',
    ])->assertForbidden();
    expect($announcement->fresh()->visibility->value)->toBe('private');
});

test('Officer can pin and unpin a published shared notice and cannot pin a draft', function () {
    $officer = User::factory()->officer()->create();
    $announcement = Announcement::factory()->published()->create();

    $this->actingAs($officer)->post(route('officer.announcements.pin', $announcement))->assertRedirect();
    $this->get(route('officer.announcements.index'))
        ->assertInertia(fn ($page) => $page->where('announcements.0.is_pinned', true));

    $this->post(route('officer.announcements.unpin', $announcement))->assertRedirect();
    $this->get(route('officer.announcements.index'))
        ->assertInertia(fn ($page) => $page->where('announcements.0.is_pinned', false));

    $this->post(route('officer.announcements.unpublish', $announcement))->assertRedirect();
    $this->post(route('officer.announcements.pin', $announcement))->assertSessionHasErrors('pin');
    $this->get(route('officer.announcements.index'))
        ->assertInertia(fn ($page) => $page->where('announcements.0.is_published', false)->where('announcements.0.is_pinned', false));
});

test('each published Announcement controls its own reader audience', function () {
    $public = Announcement::factory()->published()->create(['visibility' => 'public']);
    $private = Announcement::factory()->published()->create(['visibility' => 'private']);
    $hidden = Announcement::factory()->published()->create(['visibility' => 'hidden']);
    $draft = Announcement::factory()->draft()->create(['visibility' => 'public']);
    $member = User::factory()->create();
    Membership::factory()->owner()->create(['user_id' => $member->id]);
    $officer = User::factory()->officer()->create();

    $this->get(route('announcements.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->has('announcements', 1)->where('announcements.0.id', $public->id));
    foreach ([$private, $hidden, $draft] as $notice) {
        $this->get(route('announcements.show', $notice))->assertNotFound();
    }
    $this->actingAs($member)->get(route('announcements.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->has('announcements', 2));
    $this->get(route('announcements.show', $private))->assertOk();
    $this->get(route('announcements.show', $hidden))->assertNotFound();
    $this->actingAs($officer)->get(route('announcements.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->has('announcements', 3));
    $this->get(route('announcements.show', $hidden))->assertOk();
    $this->get(route('announcements.show', $draft))->assertNotFound();
});

test('Officers save and change visibility on an individual Announcement', function () {
    $officer = User::factory()->officer()->create();
    $this->actingAs($officer)->post(route('officer.announcements.store'), [
        'title' => 'Public notice', 'body' => '<p>For everyone.</p>', 'visibility' => 'public',
    ])->assertRedirect();
    $this->get(route('officer.announcements.index'))->assertInertia(fn ($page) => $page
        ->where('announcements.0.visibility', 'public')->where('announcements.0.visibility_label', 'Public'));
    $announcement = Announcement::query()->sole();
    $this->put(route('officer.announcements.update', $announcement), [
        'title' => 'Private notice', 'body' => '<p>For Members.</p>', 'visibility' => 'private',
    ])->assertRedirect();
    $this->get(route('officer.announcements.edit', $announcement))->assertInertia(fn ($page) => $page
        ->where('announcement.visibility', 'private')->has('visibilityOptions', 3));
    $this->put(route('officer.announcements.update', $announcement), [
        'title' => 'Private notice', 'body' => '<p>For Members.</p>', 'visibility' => 'invalid',
    ])->assertSessionHasErrors('visibility');
    $this->get(route('officer.announcements.edit', $announcement))->assertInertia(fn ($page) => $page->where('announcement.visibility', 'private'));
});

test('attachment downloads follow each notice audience including drafts', function () {
    Storage::fake('local');
    $attachments = [];
    foreach (['public', 'private', 'hidden', 'draft'] as $visibility) {
        $notice = Announcement::factory()->create([
            'visibility' => $visibility === 'draft' ? 'public' : $visibility,
            'published_at' => $visibility === 'draft' ? null : now(),
        ]);
        $path = UploadedFile::fake()->image($visibility.'.png')->store('announcement-attachments', 'local');
        $attachments[$visibility] = $notice->attachments()->create([
            'path' => $path, 'original_filename' => $visibility.'.png',
            'mime_type' => 'image/png', 'disk' => 'local', 'size' => 1024,
        ]);
    }
    $this->get(route('announcements.attachments.show', $attachments['public']))->assertOk();
    foreach (['private', 'hidden', 'draft'] as $visibility) {
        $this->get(route('announcements.attachments.show', $attachments[$visibility]))->assertNotFound();
    }
    $member = User::factory()->create();
    Membership::factory()->resident()->create(['user_id' => $member->id]);
    $this->actingAs($member)->get(route('announcements.attachments.show', $attachments['private']))->assertOk();
    $this->get(route('announcements.attachments.show', $attachments['hidden']))->assertNotFound();
    $this->get(route('announcements.attachments.show', $attachments['draft']))->assertNotFound();
    $this->actingAs(User::factory()->officer()->create());
    foreach ($attachments as $attachment) {
        $this->get(route('announcements.attachments.show', $attachment))->assertOk();
    }
});

test('feed search cannot expose private notices to guests or accounts without live Memberships', function () {
    Announcement::factory()->published()->create(['body' => '<p>Classified notice</p>', 'visibility' => 'private']);
    Announcement::factory()->published()->create(['body' => '<p>Public notice</p>', 'visibility' => 'public']);
    $this->get(route('announcements.index', ['search' => 'Classified']))->assertOk()
        ->assertInertia(fn ($page) => $page->has('announcements', 0));
    $user = User::factory()->create();
    Membership::factory()->owner()->create(['user_id' => $user->id, 'ended_at' => now()->subDay()]);
    $this->actingAs($user)->get(route('announcements.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->has('announcements', 1)->where('announcements.0.visibility', 'public'));
});

test('upgraded notices retain the legacy feed audience', function (string $visibility) {
    $officer = User::factory()->officer()->create();
    $announcement = Announcement::factory()->published()->create();
    $migration = require database_path('migrations/2026_10_08_173725_add_visibility_to_announcements_table.php');
    $migration->down();
    AssociationSetting::current()->update(['announcements_page_visibility' => $visibility]);
    $migration->up();
    $this->actingAs($officer)->get(route('officer.announcements.index'))->assertInertia(fn ($page) => $page
        ->where('announcements.0.id', $announcement->id)->where('announcements.0.visibility', $visibility));
})->with(['public', 'private', 'hidden']);
