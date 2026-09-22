<?php

namespace App\Actions\PropertyProfiles;

use App\Models\Property;
use App\Models\PropertyProfile;
use Illuminate\Support\Facades\DB;

class UpdatePropertyProfile
{
    /**
     * @param  array{
     *     household_members?: list<array{name: string}>,
     *     emergency_contacts?: list<array{name: string, contact_number: string, relationship: string}>,
     *     vehicles?: list<array{year: int|string, make: string, model: string, plate: string, sticker_number: string}>,
     * }  $data
     */
    public function handle(Property $property, array $data): PropertyProfile
    {
        return DB::transaction(function () use ($property, $data): PropertyProfile {
            Property::query()
                ->whereKey($property->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            return PropertyProfile::query()->updateOrCreate(
                ['property_id' => $property->id],
                [
                    'household_members' => array_map(
                        fn (array $member): array => ['name' => trim($member['name'])],
                        $data['household_members'] ?? [],
                    ),
                    'emergency_contacts' => array_map(
                        fn (array $contact): array => [
                            'name' => trim($contact['name']),
                            'contact_number' => trim($contact['contact_number']),
                            'relationship' => trim($contact['relationship']),
                        ],
                        $data['emergency_contacts'] ?? [],
                    ),
                    'vehicles' => array_map(
                        fn (array $vehicle): array => [
                            'year' => (int) $vehicle['year'],
                            'make' => trim($vehicle['make']),
                            'model' => trim($vehicle['model']),
                            'plate' => trim($vehicle['plate']),
                            'sticker_number' => trim($vehicle['sticker_number']),
                        ],
                        $data['vehicles'] ?? [],
                    ),
                    'saved_at' => now(),
                ],
            );
        });
    }
}
