<?php

use App\Models\AssociationSetting;
use App\Models\Charge;
use App\Models\FeeType;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

test('Officer can set the levy day of month', function () {
    $user = User::factory()->officer()->create();

    $this->actingAs($user)
        ->put(route('officer.levy-settings.update'), [
            'levy_day_of_month' => 15,
        ])
        ->assertRedirect(route('officer.levy-settings.edit'));

    expect(AssociationSetting::current()->levy_day_of_month)->toBe(15);
});

test('scheduled generation runs on the configured levy day in Asia/Manila', function () {
    AssociationSetting::current()->update(['levy_day_of_month' => 15]);
    Property::factory()->create();
    FeeType::factory()->create(['name' => 'Guard', 'amount' => '200.00']);

    Carbon::setTestNow(Carbon::parse('2026-09-15 00:10:00', 'Asia/Manila'));

    Artisan::call('charges:generate', ['--scheduled' => true]);

    expect(Charge::query()->count())->toBe(1)
        ->and(Charge::query()->first()->month)->toBe(9);

    Carbon::setTestNow();
});

test('scheduled generation uses the last day when the levy day is missing in the month', function () {
    AssociationSetting::current()->update(['levy_day_of_month' => 31]);
    Property::factory()->create();
    FeeType::factory()->create(['name' => 'Guard', 'amount' => '200.00']);

    Carbon::setTestNow(Carbon::parse('2026-02-28 00:10:00', 'Asia/Manila'));

    Artisan::call('charges:generate', ['--scheduled' => true]);

    expect(Charge::query()->count())->toBe(1)
        ->and(Charge::query()->first()->month)->toBe(2);

    Carbon::setTestNow();
});

test('scheduled generation skips non-levy days', function () {
    AssociationSetting::current()->update(['levy_day_of_month' => 15]);
    Property::factory()->create();
    FeeType::factory()->create(['name' => 'Guard', 'amount' => '200.00']);

    Carbon::setTestNow(Carbon::parse('2026-09-14 00:10:00', 'Asia/Manila'));

    Artisan::call('charges:generate', ['--scheduled' => true]);

    expect(Charge::query()->count())->toBe(0);

    Carbon::setTestNow();
});
