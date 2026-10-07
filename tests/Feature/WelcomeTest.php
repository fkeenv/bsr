<?php

use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

test('homepage reflects whether account registration is enabled', function (bool $registrationEnabled) {
    config()->set('fortify.features', $registrationEnabled ? [Features::registration()] : []);

    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
        ->where('canRegister', $registrationEnabled)
        ->where('auth.user', null)
        ->missing('announcements')
    );
})->with(['registration enabled' => true, 'registration disabled' => false]);
