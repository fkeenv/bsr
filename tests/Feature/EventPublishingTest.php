<?php

use App\Enums\EventAudience;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\EventRegistrationRules;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(Carbon::parse('2026-10-14 00:00:00', 'UTC'));
});

test('Event discovery limits guests and accounts without Memberships to published Public Events', function () {
    $public = Event::factory()->published()->create();
    Event::factory()->published()->create(['audience' => EventAudience::MembersOnly]);
    Event::factory()->create();
    $this->get('/events')->assertInertia(fn (Assert $page) => $page->component('events/Index')->has('events', 1)->where('events.0.id', $public->id));
});

function freeEventDetails(): array
{
    return ['title' => 'Community afternoon', 'description' => '<p>Come together.</p>', 'venue' => 'Clubhouse',
        'starts_at' => '2027-01-20T16:00', 'ends_at' => '2027-01-20T18:00', 'registration_deadline' => '2027-01-19T12:00',
        'audience' => 'public', 'capacity' => 10, 'price_centavos' => 0];
}

test('live Membership holders discover Members only Events and access is checked on each detail request', function () {
    $event = Event::factory()->published()->create(['audience' => EventAudience::MembersOnly]);
    $user = User::factory()->member()->create();
    $this->get(route('events.show', $event))->assertNotFound();
    $this->actingAs($user)->get(route('events.index'))->assertInertia(fn (Assert $page) => $page->has('events', 1));
    $this->get(route('events.show', $event))->assertInertia(fn (Assert $page) => $page->where('event.id', $event->id));
    $user->memberships()->update(['ended_at' => now()]);
    $this->get(route('events.show', $event))->assertNotFound();
    $this->get(route('events.index'))->assertInertia(fn (Assert $page) => $page->has('events', 0));
});

test('signed in accounts and Super Admin without live Memberships see only published Public Events', function (bool $root) {
    $public = Event::factory()->published()->create();
    $private = Event::factory()->published()->create(['audience' => EventAudience::MembersOnly]);
    $draft = Event::factory()->create();
    $this->actingAs($root ? User::factory()->superAdmin()->create() : User::factory()->create());
    $this->get(route('events.index'))->assertInertia(fn (Assert $page) => $page->has('events', 1)->where('events.0.id', $public->id));
    $this->get(route('events.show', $private))->assertNotFound();
    $this->get(route('events.show', $draft))->assertNotFound();
})->with([false, true]);

test('Super Admin creates sanitized free drafts in Manila time and publishes them', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $data = freeEventDetails();
    $data['description'] = '<script>alert(1)</script><p>Welcome <strong>neighbors</strong>.</p>';
    $this->post(route('officer.events.store'), $data)->assertSessionHasNoErrors();
    $event = Event::query()->sole();
    expect($event->status)->toBe(EventStatus::Draft);
    expect($event->starts_at->format('Y-m-d H:i'))->toBe('2027-01-20 08:00');
    expect($event->description)->toContain('<strong>neighbors</strong>')->not->toContain('script');
    $this->post(route('officer.events.publish', $event))->assertSessionHasNoErrors();
    expect($event->fresh()->status)->toBe(EventStatus::Published);
    $this->get(route('events.show', $event))->assertInertia(fn (Assert $page) => $page->missing('event.created_by_user_id')->missing('event.image_path')->missing('event.registrations'));
});

test('Event grants are initially ungranted and management does not follow review or check in grants', function () {
    $this->seed(RoleSeeder::class);
    foreach (['officer', 'administrator'] as $name) {
        expect(Role::findByName($name)->permissions()->where('name', 'like', 'events.%')->count())->toBe(0);
    }
    $officer = User::factory()->officer()->create();
    $event = Event::factory()->create();
    $this->actingAs($officer);
    $this->get(route('officer.events.index'))->assertForbidden();
    $this->get(route('officer.events.create'))->assertForbidden();
    $this->get(route('officer.events.edit', $event))->assertForbidden();
    $this->post(route('officer.events.store'), freeEventDetails())->assertForbidden();
    $this->put(route('officer.events.update', $event), freeEventDetails())->assertForbidden();
    $this->post(route('officer.events.publish', $event))->assertForbidden();
    $this->post(route('officer.events.cancel', $event))->assertForbidden();
    $role = Role::findByName('officer');
    $role->givePermissionTo(['events.registrations.read', 'events.payments.review', 'events.refunds.review', 'events.check-in']);
    $this->get(route('officer.events.index'))->assertForbidden();
    $role->givePermissionTo('events.manage');
    $this->get(route('officer.events.index'))->assertOk();
    $this->post(route('officer.events.store'), freeEventDetails())->assertSessionHasNoErrors();
});

test('invalid free Event details cannot be saved', function (string $field, mixed $value) {
    $this->actingAs(User::factory()->superAdmin()->create());
    $data = freeEventDetails();
    $data[$field] = $value;
    $this->post(route('officer.events.store'), $data)->assertSessionHasErrors($field);
    expect(Event::count())->toBe(0);
})->with([
    ['title', ''], ['description', '<p></p>'], ['venue', ''], ['starts_at', 'not a date'],
    ['ends_at', '2027-01-20T15:00'], ['registration_deadline', '2027-01-21T12:00'],
    ['audience', 'hidden'], ['capacity', -1], ['capacity', 1.5], ['price_centavos', -1], ['price_centavos', 100],
]);

test('publishing revalidates chronology deadline and free price of stored drafts', function (array $invalid) {
    $this->actingAs(User::factory()->superAdmin()->create());
    $event = Event::factory()->create($invalid);
    $this->post(route('officer.events.publish', $event))->assertSessionHasErrors('event');
    expect($event->fresh()->status)->toBe(EventStatus::Draft);
})->with([
    [['starts_at' => '2026-10-01 00:00:00']], [['ends_at' => '2026-10-01 00:00:00']],
    [['registration_deadline' => '2026-10-01 00:00:00']], [['registration_deadline' => '2028-01-01 00:00:00']],
    [['price_centavos' => 100]], [['capacity' => -1]], [['description' => '<p></p>']],
]);

test('registration rules lock audience even after cancellation and reject capacity below reserved places', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->post(route('officer.events.store'), freeEventDetails());
    $event = Event::query()->sole();
    $registration = EventRegistration::factory()->create(['event_id' => $event->id, 'attendee_count' => 4]);
    $data = freeEventDetails();
    $data['capacity'] = 3;
    $this->put(route('officer.events.update', $event), $data)->assertSessionHasErrors('capacity');
    $data['capacity'] = 4;
    $this->put(route('officer.events.update', $event), $data)->assertSessionHasNoErrors();
    expect($event->fresh()->capacity)->toBe(4);
    $registration->update(['cancelled_at' => now()]);
    $data['capacity'] = 0;
    $this->put(route('officer.events.update', $event), $data)->assertSessionHasNoErrors();
    $data['audience'] = 'members-only';
    $this->put(route('officer.events.update', $event), $data)->assertSessionHasErrors('audience');
    expect($event->fresh()->audience)->toBe(EventAudience::Public);
    expect(fn () => app(EventRegistrationRules::class)->ensureCanChange($event->fresh(), [...$data, 'audience' => 'public', 'price_centavos' => 100]))->toThrow(ValidationException::class, 'Price is locked');
});

test('cancelled Events retain history and cannot be published or edited again', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $event = Event::factory()->published()->create();
    EventRegistration::factory()->create(['event_id' => $event->id]);
    $this->post(route('officer.events.cancel', $event))->assertSessionHasNoErrors();
    $cancelledAt = $event->fresh()->cancelled_at;
    $this->post(route('officer.events.cancel', $event))->assertSessionHasNoErrors();
    expect($event->fresh()->cancelled_at->equalTo($cancelledAt))->toBeTrue();
    expect($event->fresh()->registrations()->count())->toBe(1);
    $this->post(route('officer.events.publish', $event))->assertSessionHasErrors('event');
    $this->put(route('officer.events.update', $event), freeEventDetails())->assertSessionHasErrors('event');
    $this->get(route('events.show', $event))->assertNotFound();
});

test('published schedules are locked until rescheduling supports history', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->post(route('officer.events.store'), freeEventDetails());
    $event = Event::query()->sole();
    $this->post(route('officer.events.publish', $event));
    $data = freeEventDetails();
    $data['starts_at'] = '2027-01-20T17:00';
    $this->put(route('officer.events.update', $event), $data)->assertSessionHasErrors('starts_at');
    $this->post(route('officer.events.publish', $event))->assertSessionHasNoErrors();
});

test('Event images use private storage with current audience checks', function () {
    Storage::fake('local');
    $root = User::factory()->superAdmin()->create();
    $this->actingAs($root)->post(route('officer.events.store'), [...freeEventDetails(), 'image' => UploadedFile::fake()->image('event.jpg')])->assertSessionHasNoErrors();
    $event = Event::query()->sole();
    Storage::disk('local')->assertExists($event->image_path);
    $this->get(route('events.image', $event))->assertOk();
    $this->post(route('officer.events.publish', $event));
    $this->post(route('logout'));
    $this->get(route('events.image', $event))->assertOk();
    $event->update(['audience' => EventAudience::MembersOnly]);
    $this->get(route('events.image', $event))->assertNotFound();
    $event->update(['audience' => EventAudience::Public, 'status' => EventStatus::Cancelled]);
    $this->get(route('events.image', $event))->assertNotFound();
});

test('Events are listed by schedule with deterministic ties and without full descriptions', function () {
    $later = Event::factory()->published()->create(['starts_at' => '2027-01-20 08:00:00']);
    $first = Event::factory()->published()->create(['starts_at' => '2027-01-19 08:00:00']);
    $second = Event::factory()->published()->create(['starts_at' => '2027-01-19 08:00:00']);
    $this->get(route('events.index'))->assertInertia(fn (Assert $page) => $page->has('events', 3)
        ->where('events.0.id', $first->id)->where('events.1.id', $second->id)->where('events.2.id', $later->id)
        ->where('events.0.description', '')->missing('events.0.image_path'));
});

test('unlimited capacity is valid and registered Events cannot be deleted', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->post(route('officer.events.store'), [...freeEventDetails(), 'capacity' => null])->assertSessionHasNoErrors();
    $event = Event::query()->sole();
    expect($event->capacity)->toBeNull();
    EventRegistration::factory()->create(['event_id' => $event->id]);
    expect(fn () => $event->delete())->toThrow(QueryException::class);
    expect(Event::find($event->id))->not->toBeNull();
});

test('Event uploads reject non images and missing images return 404', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->post(route('officer.events.store'), [...freeEventDetails(), 'image' => UploadedFile::fake()->create('notice.pdf', 1, 'application/pdf')])->assertSessionHasErrors('image');
    $event = Event::factory()->published()->create();
    $this->get(route('events.image', $event))->assertNotFound();
});
