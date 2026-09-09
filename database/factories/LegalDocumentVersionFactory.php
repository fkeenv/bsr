<?php

namespace Database\Factories;

use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalDocumentVersion>
 */
class LegalDocumentVersionFactory extends Factory
{
    protected $model = LegalDocumentVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => LegalDocumentType::TermsOfService,
            'body' => fake()->paragraphs(3, true),
            'published_at' => now(),
        ];
    }

    public function termsOfService(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LegalDocumentType::TermsOfService,
        ]);
    }

    public function privacyPolicy(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LegalDocumentType::PrivacyPolicy,
        ]);
    }
}
