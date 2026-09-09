<?php

namespace Database\Factories;

use App\Enums\LegalDocumentType;
use App\Enums\MembershipApplicationStatus;
use App\Models\LegalDocumentVersion;
use App\Models\MembershipApplication;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipApplication>
 */
class MembershipApplicationFactory extends Factory
{
    protected $model = MembershipApplication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'property_id' => Property::factory(),
            'status' => MembershipApplicationStatus::Pending,
            'note' => null,
            'terms_of_service_version_id' => LegalDocumentVersion::factory()->termsOfService(),
            'privacy_policy_version_id' => LegalDocumentVersion::factory()->privacyPolicy(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipApplicationStatus::Pending,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipApplicationStatus::Rejected,
            'reviewed_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipApplicationStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function withCurrentLegalDocuments(): static
    {
        return $this->state(function (array $attributes) {
            $terms = LegalDocumentVersion::current(LegalDocumentType::TermsOfService)
                ?? LegalDocumentVersion::factory()->termsOfService()->create();
            $privacy = LegalDocumentVersion::current(LegalDocumentType::PrivacyPolicy)
                ?? LegalDocumentVersion::factory()->privacyPolicy()->create();

            return [
                'terms_of_service_version_id' => $terms->id,
                'privacy_policy_version_id' => $privacy->id,
            ];
        });
    }
}
