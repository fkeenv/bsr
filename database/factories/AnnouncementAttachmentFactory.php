<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnouncementAttachment>
 */
class AnnouncementAttachmentFactory extends Factory
{
    protected $model = AnnouncementAttachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'announcement_id' => Announcement::factory(),
            'path' => 'announcement-attachments/'.fake()->uuid().'.pdf',
            'original_filename' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'disk' => 'local',
            'size' => fake()->numberBetween(1_000, 500_000),
        ];
    }
}
