<?php

use App\Enums\PlatformRole;
use App\Models\AssociationSetting;
use App\Models\Membership;
use App\Models\Property;
use App\Models\User;

test('AssociationSetting seeds letterhead and how-to-pay defaults', function () {
    $settings = AssociationSetting::current();

    expect($settings->letterhead_name)->toBe('Blessed Sacrament Residences Homeowners Association')
        ->and($settings->letterhead_short_name)->toBe('BSR HOA')
        ->and($settings->letterhead_address_lines)->toBe([
            'Blessed Sacrament Residences',
            'Quezon City, Metro Manila',
        ])
        ->and($settings->letterhead_contact)->toBe('treasurer@bsr.example (placeholder)')
        ->and($settings->letterhead_treasurer)->toBe('Treasurer — Maria Santos (placeholder)')
        ->and($settings->payment_channels)->toBe([
            [
                'method' => 'Cash',
                'detail' => 'Pay the Treasurer in person; ask for a handwritten receipt.',
            ],
            [
                'method' => 'Bank transfer',
                'detail' => 'BDO · Account name TBA · Account no. TBA',
            ],
            [
                'method' => 'GCash',
                'detail' => 'TBA — name the Property (Block + Lot) in the note.',
            ],
            [
                'method' => 'Maya',
                'detail' => 'TBA — name the Property (Block + Lot) in the note.',
            ],
        ]);
});

test('Officer can open Printed Bill settings', function () {
    $officer = User::factory()->officer()->create();

    $this->actingAs($officer)
        ->get(route('officer.bill-settings.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('officer/bill-settings/Edit')
            ->where('letterheadName', 'Blessed Sacrament Residences Homeowners Association')
            ->has('paymentChannels', 4));
});

test('Officer can update Printed Bill letterhead and how-to-pay settings', function () {
    $officer = User::factory()->officer()->create();

    $this->actingAs($officer)
        ->put(route('officer.bill-settings.update'), [
            'letterhead_name' => 'BSR Homeowners Association',
            'letterhead_short_name' => 'BSR',
            'letterhead_address_lines' => [
                'Blessed Sacrament Residences',
                'Quezon City',
            ],
            'letterhead_contact' => 'billing@bsr.example',
            'letterhead_treasurer' => 'Treasurer — Ana Reyes',
            'payment_channels' => [
                [
                    'method' => 'Cash',
                    'detail' => 'Pay at the clubhouse.',
                ],
                [
                    'method' => 'GCash',
                    'detail' => '09XX · name Block + Lot',
                ],
            ],
        ])
        ->assertRedirect(route('officer.bill-settings.edit'));

    $settings = AssociationSetting::current()->fresh();

    expect($settings->letterhead_name)->toBe('BSR Homeowners Association')
        ->and($settings->letterhead_short_name)->toBe('BSR')
        ->and($settings->letterhead_address_lines)->toBe([
            'Blessed Sacrament Residences',
            'Quezon City',
        ])
        ->and($settings->letterhead_contact)->toBe('billing@bsr.example')
        ->and($settings->letterhead_treasurer)->toBe('Treasurer — Ana Reyes')
        ->and($settings->payment_channels)->toBe([
            [
                'method' => 'Cash',
                'detail' => 'Pay at the clubhouse.',
            ],
            [
                'method' => 'GCash',
                'detail' => '09XX · name Block + Lot',
            ],
        ]);
});

test('Members cannot update Printed Bill settings', function () {
    $member = User::factory()->create();
    $property = Property::factory()->create();
    Membership::factory()->owner()->create([
        'user_id' => $member->id,
        'property_id' => $property->id,
    ]);
    $member->assignPlatformRole(PlatformRole::Member);

    $this->actingAs($member)
        ->put(route('officer.bill-settings.update'), [
            'letterhead_name' => 'Hacked',
            'letterhead_short_name' => 'X',
            'letterhead_address_lines' => ['Nowhere'],
            'letterhead_contact' => 'x@example.com',
            'letterhead_treasurer' => 'Nobody',
            'payment_channels' => [
                ['method' => 'Cash', 'detail' => 'Nope'],
            ],
        ])
        ->assertForbidden();
});
