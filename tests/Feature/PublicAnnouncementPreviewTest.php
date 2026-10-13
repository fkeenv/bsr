<?php

use App\Enums\AnnouncementVisibility;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->withoutVite());

test('landing previews contain only published Public Announcements for every account audience', function (string $audience) {
    $public = Announcement::factory()->published()->create(['visibility' => AnnouncementVisibility::Public]);
    Announcement::factory()->published()->create(['visibility' => AnnouncementVisibility::Private, 'title' => 'Private secret']);
    Announcement::factory()->published()->create(['visibility' => AnnouncementVisibility::Hidden, 'title' => 'Hidden secret']);
    Announcement::factory()->draft()->create(['visibility' => AnnouncementVisibility::Public, 'title' => 'Draft secret']);
    if ($audience !== 'guest') {
        $user = match ($audience) {
            'member' => User::factory()->member()->create(), 'officer' => User::factory()->officer()->create(), 'administrator' => User::factory()->administrator()->create(), default => User::factory()->superAdmin()->create()
        };
        $this->actingAs($user);
    }
    $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('announcementPreviews', 1)->where('announcementPreviews.0.id', $public->id));
})->with(['guest', 'member', 'officer', 'administrator', 'super-admin']);

test('previews reuse pinned newest and deterministic feed order and stop at six', function () {
    $author = User::factory()->officer()->create();
    $olderPin = Announcement::factory()->published()->create(['created_by_user_id' => $author->id, 'visibility' => 'public', 'published_at' => '2026-10-10 12:00:00', 'pinned_at' => '2026-10-11 12:00:00']);
    $newerPin = Announcement::factory()->published()->create(['created_by_user_id' => $author->id, 'visibility' => 'public', 'published_at' => '2026-10-09 12:00:00', 'pinned_at' => '2026-10-12 12:00:00']);
    $latest = Announcement::factory()->published()->create(['created_by_user_id' => $author->id, 'visibility' => 'public', 'published_at' => '2026-10-13 12:00:00']);
    $tie1 = Announcement::factory()->published()->create(['created_by_user_id' => $author->id, 'visibility' => 'public', 'published_at' => '2026-10-12 12:00:00']);
    $tie2 = Announcement::factory()->published()->create(['created_by_user_id' => $author->id, 'visibility' => 'public', 'published_at' => '2026-10-12 12:00:00']);
    $sixth = Announcement::factory()->published()->create(['created_by_user_id' => $author->id, 'visibility' => 'public', 'published_at' => '2026-10-11 12:00:00']);
    Announcement::factory()->published()->count(3)->create(['created_by_user_id' => $author->id, 'visibility' => 'public', 'published_at' => '2026-10-08 12:00:00']);
    $ids = [$newerPin->id, $olderPin->id, $latest->id, $tie2->id, $tie1->id, $sixth->id];
    $this->get(route('home'))->assertInertia(fn ($page) => $page->has('announcementPreviews', 6)->where('announcementPreviews', fn ($previews) => collect($previews)->pluck('id')->all() === $ids));
});

test('landing data uses short sanitized text and no full body or attachment metadata', function () {
    $public = Announcement::factory()->published()->create(['visibility' => 'public', 'body' => '<script>secretScript()</script><style>secretStyle</style><p>Hello &amp; welcome.</p><p>'.str_repeat('Neighbors ', 60).'</p>']);
    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('announcementPreviews.0.excerpt', fn ($text) => str_starts_with($text, 'Hello & welcome. Neighbors') && ! str_contains($text, 'secret') && ! str_contains($text, '<') && mb_strlen($text) <= 183)
        ->where('announcementPreviews.0', fn ($preview) => array_keys($preview->toArray()) === ['id', 'title', 'published_at', 'excerpt', 'image_url'])->where('announcementPreviews.0.published_at', $public->published_at->toIso8601String()));
});

test('first image selection ignores PDFs and is deterministic for each Announcement', function () {
    $author = User::factory()->officer()->create();
    $first = Announcement::factory()->published()->create(['created_by_user_id' => $author->id, 'visibility' => 'public']);
    $second = Announcement::factory()->published()->create(['created_by_user_id' => $author->id, 'visibility' => 'public']);
    AnnouncementAttachment::factory()->create(['announcement_id' => $first->id]);
    $image1 = AnnouncementAttachment::factory()->create(['announcement_id' => $first->id, 'mime_type' => 'image/png', 'created_at' => '2026-10-13 12:00:00']);
    AnnouncementAttachment::factory()->create(['announcement_id' => $first->id, 'mime_type' => 'image/jpeg', 'created_at' => '2026-10-12 12:00:00']);
    $image2 = AnnouncementAttachment::factory()->create(['announcement_id' => $second->id, 'mime_type' => 'image/webp']);
    AnnouncementAttachment::factory()->create(['announcement_id' => $second->id, 'mime_type' => 'image/jpeg']);
    $this->get(route('home'))->assertInertia(fn ($page) => $page->has('announcementPreviews', 2)->where('announcementPreviews.0.image_url', route('announcements.attachments.show', $image2))->where('announcementPreviews.1.image_url', route('announcements.attachments.show', $image1)));
});

test('Announcements without images and PDF only items receive a stable local fallback', function () {
    $first = Announcement::factory()->published()->create(['visibility' => 'public']);
    $pdfOnly = Announcement::factory()->published()->create(['visibility' => 'public']);
    AnnouncementAttachment::factory()->create(['announcement_id' => $pdfOnly->id]);
    $this->get(route('home'))->assertInertia(fn ($page) => $page->has('announcementPreviews', 2)->where('announcementPreviews.0.image_url', null)->where('announcementPreviews.1.image_url', null));
    expect(file_exists(public_path('images/announcements/community-stock.webp')))->toBeTrue();
});

test('no eligible Announcements produce an empty preview list', function () {
    Announcement::factory()->draft()->create(['visibility' => 'public']);
    Announcement::factory()->published()->create(['visibility' => 'private']);
    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('announcementPreviews', []));
});

test('preview image and article URLs recheck publication and visibility on later requests', function () {
    Storage::fake('local');
    $public = Announcement::factory()->published()->create(['visibility' => 'public']);
    $image = AnnouncementAttachment::factory()->create(['announcement_id' => $public->id, 'disk' => 'local', 'path' => 'preview.jpg', 'mime_type' => 'image/jpeg']);
    Storage::disk('local')->put('preview.jpg', 'image');
    $articleUrl = route('announcements.show', $public);
    $imageUrl = route('announcements.attachments.show', $image);
    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('announcementPreviews.0.image_url', $imageUrl));
    $this->get($articleUrl)->assertOk();
    $this->get($imageUrl)->assertOk();
    $public->update(['visibility' => 'private']);
    $this->get($articleUrl)->assertNotFound();
    $this->get($imageUrl)->assertNotFound();
    $public->update(['visibility' => 'public', 'published_at' => null]);
    $this->get($articleUrl)->assertNotFound();
    $this->get($imageUrl)->assertNotFound();
});
