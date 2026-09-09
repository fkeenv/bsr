<?php

namespace App\Actions\MembershipApplications;

use App\Enums\LegalDocumentType;
use App\Enums\MembershipApplicationStatus;
use App\Models\LegalDocumentVersion;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitMembershipApplication
{
    /**
     * @param  array{
     *     property_id: int,
     *     note?: string|null,
     *     accept_terms: bool,
     *     accept_privacy: bool,
     *     household_members?: list<array{name: string}>,
     *     emergency_contacts?: list<array{name: string, contact_number: string, relationship: string}>,
     *     vehicles?: list<array{year: int|string, make: string, model: string, plate: string, sticker_number: string}>,
     * }  $data
     */
    public function handle(User $user, array $data): MembershipApplication
    {
        $property = Property::query()->active()->find($data['property_id']);

        if ($property === null) {
            throw ValidationException::withMessages([
                'property_id' => 'Select an active roster Property.',
            ]);
        }

        if (Membership::query()->live()->where('user_id', $user->id)->where('property_id', $property->id)->exists()) {
            throw ValidationException::withMessages([
                'property_id' => 'You already hold a live Membership on this Property.',
            ]);
        }

        $terms = LegalDocumentVersion::current(LegalDocumentType::TermsOfService);
        $privacy = LegalDocumentVersion::current(LegalDocumentType::PrivacyPolicy);

        if ($terms === null || $privacy === null) {
            throw ValidationException::withMessages([
                'accept_terms' => 'Terms of Service and Privacy Policy must be published before you can apply.',
            ]);
        }

        if (! $data['accept_terms'] || ! $data['accept_privacy']) {
            throw ValidationException::withMessages([
                'accept_terms' => 'You must accept the current Terms of Service and Privacy Policy.',
            ]);
        }

        return DB::transaction(function () use ($user, $data, $property, $terms, $privacy): MembershipApplication {
            $application = MembershipApplication::query()
                ->where('user_id', $user->id)
                ->where('property_id', $property->id)
                ->editable()
                ->first();

            if ($application === null) {
                $application = new MembershipApplication([
                    'user_id' => $user->id,
                    'property_id' => $property->id,
                ]);
            }

            $application->fill([
                'status' => MembershipApplicationStatus::Pending,
                'note' => $this->nullableString($data['note'] ?? null),
                'terms_of_service_version_id' => $terms->id,
                'privacy_policy_version_id' => $privacy->id,
                'reviewed_by_user_id' => null,
                'reviewed_at' => null,
            ]);
            $application->save();

            $this->syncHouseholdMembers($application, $data['household_members'] ?? []);
            $this->syncEmergencyContacts($application, $data['emergency_contacts'] ?? []);
            $this->syncVehicles($application, $data['vehicles'] ?? []);

            return $application->fresh([
                'householdMembers',
                'emergencyContacts',
                'vehicles',
            ]) ?? $application;
        });
    }

    /**
     * @param  list<array{name: string}>  $members
     */
    private function syncHouseholdMembers(MembershipApplication $application, array $members): void
    {
        $application->householdMembers()->delete();

        foreach ($members as $member) {
            $name = trim($member['name']);

            if ($name === '') {
                continue;
            }

            $application->householdMembers()->create([
                'name' => $name,
            ]);
        }
    }

    /**
     * @param  list<array{name: string, contact_number: string, relationship: string}>  $contacts
     */
    private function syncEmergencyContacts(MembershipApplication $application, array $contacts): void
    {
        $application->emergencyContacts()->delete();

        foreach ($contacts as $contact) {
            $application->emergencyContacts()->create([
                'name' => trim($contact['name']),
                'contact_number' => trim($contact['contact_number']),
                'relationship' => trim($contact['relationship']),
            ]);
        }
    }

    /**
     * @param  list<array{year: int|string, make: string, model: string, plate: string, sticker_number: string}>  $vehicles
     */
    private function syncVehicles(MembershipApplication $application, array $vehicles): void
    {
        $application->vehicles()->delete();

        foreach ($vehicles as $vehicle) {
            $application->vehicles()->create([
                'year' => (int) $vehicle['year'],
                'make' => trim($vehicle['make']),
                'model' => trim($vehicle['model']),
                'plate' => trim($vehicle['plate']),
                'sticker_number' => trim($vehicle['sticker_number']),
            ]);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
