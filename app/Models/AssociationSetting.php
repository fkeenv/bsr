<?php

namespace App\Models;

use App\Enums\AnnouncementsPageVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $levy_day_of_month
 * @property AnnouncementsPageVisibility $announcements_page_visibility
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'levy_day_of_month',
    'announcements_page_visibility',
])]
class AssociationSetting extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'levy_day_of_month' => 1,
        'announcements_page_visibility' => 'private',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'levy_day_of_month' => 'integer',
            'announcements_page_visibility' => AnnouncementsPageVisibility::class,
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'levy_day_of_month' => 1,
            'announcements_page_visibility' => AnnouncementsPageVisibility::Private,
        ]);
    }
}
