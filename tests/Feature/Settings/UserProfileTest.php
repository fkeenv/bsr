<?php

use App\Enums\PlatformRole;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Features;

test('updating account fields without title or avatar does not create a profile', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'mobile_number' => '+639171234567',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect(UserProfile::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('saving the settings form with an empty title does not create a profile', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'mobile_number' => $user->mobile_number,
            'title' => '',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect(UserProfile::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('registration does not create a user profile', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Keen Vergara',
        'email' => 'keen@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'keen@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole(PlatformRole::User))->toBeTrue()
        ->and(UserProfile::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('profile page shows null title and avatar when the user has no profile', function () {
    $user = User::factory()->create(['name' => 'Keen Vergara']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Profile')
            ->where('auth.user.title', null)
            ->where('auth.user.avatar', null)
            ->where('auth.user.name', 'Keen Vergara'));
});

test('user can update title and avatar which lazy-creates their profile', function () {
    Storage::fake('local');

    $user = User::factory()->create([
        'name' => 'Keen Vergara',
        'email' => 'keen@example.com',
    ]);

    expect(UserProfile::query()->where('user_id', $user->id)->exists())->toBeFalse();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Keen Vergara',
            'email' => 'keen@example.com',
            'mobile_number' => $user->mobile_number,
            'title' => 'Treasurer',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $profile = UserProfile::query()->where('user_id', $user->id)->first();

    expect($profile)->not->toBeNull()
        ->and($profile->title)->toBe('Treasurer')
        ->and($profile->avatar_path)->not->toBeNull();

    Storage::disk('local')->assertExists($profile->avatar_path);
});

test('user can lazy-create a profile with an avatar only', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'mobile_number' => $user->mobile_number,
            'title' => '',
            'avatar' => UploadedFile::fake()->image('avatar.png'),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $profile = UserProfile::query()->where('user_id', $user->id)->first();

    expect($profile)->not->toBeNull()
        ->and($profile->title)->toBeNull()
        ->and($profile->avatar_path)->not->toBeNull();

    Storage::disk('local')->assertExists($profile->avatar_path);
});

test('user can update title without uploading a new avatar', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create([
        'title' => 'Secretary',
        'avatar_path' => 'avatars/existing.jpg',
    ]);

    Storage::disk('local')->put($profile->avatar_path, 'existing');

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'mobile_number' => $user->mobile_number,
            'title' => 'Treasurer',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $profile->refresh();

    expect($profile->title)->toBe('Treasurer')
        ->and($profile->avatar_path)->toBe('avatars/existing.jpg');
});

test('profile page includes title and avatar url after update', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $path = 'avatars/keen.jpg';
    Storage::disk('local')->put($path, 'fake-image');

    UserProfile::factory()->for($user)->create([
        'title' => 'Treasurer',
        'avatar_path' => $path,
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Profile')
            ->where('auth.user.title', 'Treasurer')
            ->where('auth.user.avatar', route('profile.avatar.show')));
});

test('authenticated user can download their avatar', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $path = 'avatars/keen.jpg';
    Storage::disk('local')->put($path, 'fake-image-bytes');

    UserProfile::factory()->for($user)->create([
        'avatar_path' => $path,
    ]);

    $this->actingAs($user)
        ->get(route('profile.avatar.show'))
        ->assertOk();
});

test('avatar endpoint returns not found when the user has no avatar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.avatar.show'))
        ->assertNotFound();
});

test('guests cannot download an avatar', function () {
    $this->get(route('profile.avatar.show'))
        ->assertRedirect(route('login'));
});
