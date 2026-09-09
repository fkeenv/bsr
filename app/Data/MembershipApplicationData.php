<?php

namespace App\Data;

use App\Models\EmergencyContact;
use App\Models\HouseholdMember;
use App\Models\MembershipApplication;
use App\Models\Vehicle;
use Spatie\LaravelData\Data;

class MembershipApplicationData extends Data
{
    /**
     * @param  list<array{name: string}>  $household_members
     * @param  list<array{name: string, contact_number: string, relationship: string}>  $emergency_contacts
     * @param  list<array{year: int, make: string, model: string, plate: string, sticker_number: string}>  $vehicles
     */
    public function __construct(
        public int $id,
        public int $property_id,
        public string $status,
        public ?string $note,
        public int $terms_of_service_version_id,
        public int $privacy_policy_version_id,
        public array $household_members,
        public array $emergency_contacts,
        public array $vehicles,
        public ?string $property_label = null,
        public ?string $applicant_name = null,
        public ?string $applicant_email = null,
    ) {}

    public static function fromModel(MembershipApplication $application): self
    {
        $application->loadMissing(['householdMembers', 'emergencyContacts', 'vehicles', 'property', 'user']);

        return new self(
            id: $application->id,
            property_id: $application->property_id,
            status: $application->status->value,
            note: $application->note,
            terms_of_service_version_id: $application->terms_of_service_version_id,
            privacy_policy_version_id: $application->privacy_policy_version_id,
            household_members: array_values(
                $application->householdMembers
                    ->map(fn (HouseholdMember $member): array => ['name' => $member->name])
                    ->all(),
            ),
            emergency_contacts: array_values(
                $application->emergencyContacts
                    ->map(fn (EmergencyContact $contact): array => [
                        'name' => $contact->name,
                        'contact_number' => $contact->contact_number,
                        'relationship' => $contact->relationship,
                    ])
                    ->all(),
            ),
            vehicles: array_values(
                $application->vehicles
                    ->map(fn (Vehicle $vehicle): array => [
                        'year' => (int) $vehicle->year,
                        'make' => $vehicle->make,
                        'model' => $vehicle->model,
                        'plate' => $vehicle->plate,
                        'sticker_number' => $vehicle->sticker_number,
                    ])
                    ->all(),
            ),
            property_label: $application->property !== null
                ? 'Block '.$application->property->block.' · Lot '.$application->property->lot
                : null,
            applicant_name: $application->user?->name,
            applicant_email: $application->user?->email,
        );
    }
}
