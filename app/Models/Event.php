<?php

namespace App\Models;

use App\Enums\EventAudience;
use App\Enums\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string|null $image_path
 * @property string $venue
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon $registration_deadline
 * @property EventAudience $audience
 * @property EventStatus $status
 * @property int|null $capacity
 * @property int $price_centavos
 * @property Carbon|null $published_at
 * @property Carbon|null $cancelled_at
 * @property int $created_by_user_id
 * @property int|null $updated_by_user_id
 */
#[Fillable(['title', 'description', 'image_path', 'venue', 'starts_at', 'ends_at', 'registration_deadline', 'audience', 'status', 'capacity', 'price_centavos', 'published_at', 'cancelled_at', 'created_by_user_id', 'updated_by_user_id'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['audience' => EventAudience::class, 'status' => EventStatus::class, 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'registration_deadline' => 'datetime', 'published_at' => 'datetime', 'cancelled_at' => 'datetime', 'capacity' => 'integer', 'price_centavos' => 'integer'];
    }

    /** @return HasMany<EventRegistration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }
}
