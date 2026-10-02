<?php

namespace App\Models;

use App\Enums\OnboardingExperience;
use App\Enums\OnboardingStep;
use Database\Factories\OnboardingProgressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property OnboardingExperience $experience
 * @property int $version
 * @property Carbon|null $tour_acknowledged_at
 * @property list<string> $completed_steps
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'user_id',
    'experience',
    'version',
    'tour_acknowledged_at',
    'completed_steps',
])]
class OnboardingProgress extends Model
{
    /** @use HasFactory<OnboardingProgressFactory> */
    use HasFactory;

    protected $table = 'onboarding_progress';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'experience' => OnboardingExperience::class,
            'version' => 'integer',
            'tour_acknowledged_at' => 'datetime',
            'completed_steps' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasCompleted(OnboardingStep $step): bool
    {
        return in_array($step->value, $this->completed_steps, true);
    }
}
