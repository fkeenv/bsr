<?php

namespace App\Data;

use App\Enums\OnboardingStep;
use App\Models\OnboardingProgress;
use Spatie\LaravelData\Data;

class OnboardingData extends Data
{
    /**
     * @param  list<string>  $steps
     * @param  list<string>  $completed_steps
     */
    public function __construct(
        public string $experience,
        public int $version,
        public bool $tour_acknowledged,
        public array $steps,
        public array $completed_steps,
    ) {}

    public static function fromModel(OnboardingProgress $progress): self
    {
        $steps = array_map(
            fn (OnboardingStep $step): string => $step->value,
            $progress->experience->steps(),
        );

        return new self(
            experience: $progress->experience->value,
            version: $progress->version,
            tour_acknowledged: $progress->tour_acknowledged_at !== null,
            steps: $steps,
            completed_steps: array_values(array_intersect($steps, $progress->completed_steps)),
        );
    }
}
