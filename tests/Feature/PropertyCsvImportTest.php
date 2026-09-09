<?php

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('Super Admin can import a CSV that creates roster rows', function () {
    $user = User::factory()->superAdmin()->create();
    $csv = UploadedFile::fake()->createWithContent(
        'roster.csv',
        implode("\n", [
            'block,lot,street_address,recorded_owner_name,opening_balance',
            '1,1,1 Oak St,Ana,100.00',
            '1,2,,Ben,0',
        ]),
    );

    $this->actingAs($user)
        ->post(route('officer.properties.import.store'), ['csv' => $csv])
        ->assertRedirect(route('officer.properties.index'));

    $this->assertDatabaseHas('properties', [
        'block' => '1',
        'lot' => '1',
        'street_address' => '1 Oak St',
        'recorded_owner_name' => 'Ana',
        'opening_balance' => '100.00',
    ]);

    $this->assertDatabaseHas('properties', [
        'block' => '1',
        'lot' => '2',
        'recorded_owner_name' => 'Ben',
        'opening_balance' => '0.00',
    ]);
});

test('CSV re-upload updates address and recorded owner but not Opening Balance', function () {
    $user = User::factory()->superAdmin()->create();
    Property::factory()->create([
        'block' => '2',
        'lot' => '2',
        'street_address' => 'Old St',
        'recorded_owner_name' => 'Old Name',
        'opening_balance' => '500.00',
    ]);

    $csv = UploadedFile::fake()->createWithContent(
        'roster.csv',
        implode("\n", [
            'block,lot,street_address,recorded_owner_name,opening_balance',
            '2,2,New St,New Name,1.00',
        ]),
    );

    $this->actingAs($user)
        ->post(route('officer.properties.import.store'), ['csv' => $csv])
        ->assertRedirect(route('officer.properties.index'));

    $property = Property::query()->where('block', '2')->where('lot', '2')->firstOrFail();

    expect($property->street_address)->toBe('New St')
        ->and($property->recorded_owner_name)->toBe('New Name')
        ->and($property->opening_balance)->toBe('500.00');
});

test('CSV import never deletes existing Properties', function () {
    $user = User::factory()->superAdmin()->create();
    Property::factory()->create(['block' => '9', 'lot' => '9']);

    $csv = UploadedFile::fake()->createWithContent(
        'roster.csv',
        "block,lot\n8,8\n",
    );

    $this->actingAs($user)
        ->post(route('officer.properties.import.store'), ['csv' => $csv])
        ->assertRedirect(route('officer.properties.index'));

    expect(Property::query()->where('block', '9')->where('lot', '9')->exists())->toBeTrue()
        ->and(Property::query()->where('block', '8')->where('lot', '8')->exists())->toBeTrue();
});

test('plain User Account cannot import the Property CSV', function () {
    $user = User::factory()->create();
    $csv = UploadedFile::fake()->createWithContent('roster.csv', "block,lot\n1,1\n");

    $this->actingAs($user)
        ->post(route('officer.properties.import.store'), ['csv' => $csv])
        ->assertRedirect(route('membership-application.create'));
});

test('Officer surface users can open the CSV import page and download the sample', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('officer.properties.import.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('officer/properties/Import')
            ->where('canImport', true)
        );

    $response = $this->actingAs($user)
        ->get(route('officer.properties.import.sample'));

    $response->assertOk()
        ->assertDownload('property-roster-sample.csv');

    expect($response->streamedContent())
        ->toContain('block,lot,street_address,recorded_owner_name,opening_balance')
        ->toContain('1,1,12 Rose St,Juan Dela Cruz,100.00');
});

test('plain User Account cannot download the sample Property CSV', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('officer.properties.import.sample'))
        ->assertRedirect(route('membership-application.create'));
});
