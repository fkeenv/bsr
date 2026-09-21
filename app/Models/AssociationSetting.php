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
 * @property string $letterhead_name
 * @property string $letterhead_short_name
 * @property list<string> $letterhead_address_lines
 * @property string $letterhead_contact
 * @property string $letterhead_treasurer
 * @property list<array{method: string, detail: string}> $payment_channels
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'levy_day_of_month',
    'announcements_page_visibility',
    'letterhead_name',
    'letterhead_short_name',
    'letterhead_address_lines',
    'letterhead_contact',
    'letterhead_treasurer',
    'payment_channels',
])]
class AssociationSetting extends Model
{
    /**
     * @return list<string>
     */
    public static function defaultLetterheadAddressLines(): array
    {
        return [
            'Blessed Sacrament Residences',
            'Quezon City, Metro Manila',
        ];
    }

    /**
     * @return list<array{method: string, detail: string}>
     */
    public static function defaultPaymentChannels(): array
    {
        return [
            [
                'method' => 'Cash',
                'detail' => 'Pay the Treasurer in person; ask for a handwritten receipt.',
            ],
            [
                'method' => 'Bank transfer',
                'detail' => 'BDO · Account name TBA · Account no. TBA',
            ],
            [
                'method' => 'GCash',
                'detail' => 'TBA — name the Property (Block + Lot) in the note.',
            ],
            [
                'method' => 'Maya',
                'detail' => 'TBA — name the Property (Block + Lot) in the note.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function printedBillDefaults(): array
    {
        return [
            'letterhead_name' => 'Blessed Sacrament Residences Homeowners Association',
            'letterhead_short_name' => 'BSR HOA',
            'letterhead_address_lines' => self::defaultLetterheadAddressLines(),
            'letterhead_contact' => 'treasurer@bsr.example (placeholder)',
            'letterhead_treasurer' => 'Treasurer — Maria Santos (placeholder)',
            'payment_channels' => self::defaultPaymentChannels(),
        ];
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'levy_day_of_month' => 1,
        'announcements_page_visibility' => 'private',
        'letterhead_name' => 'Blessed Sacrament Residences Homeowners Association',
        'letterhead_short_name' => 'BSR HOA',
        'letterhead_contact' => 'treasurer@bsr.example (placeholder)',
        'letterhead_treasurer' => 'Treasurer — Maria Santos (placeholder)',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'levy_day_of_month' => 'integer',
            'announcements_page_visibility' => AnnouncementsPageVisibility::class,
            'letterhead_address_lines' => 'array',
            'payment_channels' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'levy_day_of_month' => 1,
            'announcements_page_visibility' => AnnouncementsPageVisibility::Private,
            ...self::printedBillDefaults(),
        ]);
    }
}
