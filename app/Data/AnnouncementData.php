<?php

namespace App\Data;

use App\Models\Announcement;
use Spatie\LaravelData\Data;

class AnnouncementData extends Data
{
    /**
     * @param  list<AnnouncementAttachmentData>  $attachments
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $body,
        public bool $is_published,
        public bool $is_pinned,
        public ?string $published_at,
        public ?string $pinned_at,
        public ?string $updated_at,
        public array $attachments,
    ) {}

    public static function fromModel(Announcement $announcement): self
    {
        $announcement->loadMissing('attachments');

        return new self(
            id: $announcement->id,
            title: $announcement->title,
            body: $announcement->body,
            is_published: $announcement->isPublished(),
            is_pinned: $announcement->isPinned(),
            published_at: $announcement->published_at?->toIso8601String(),
            pinned_at: $announcement->pinned_at?->toIso8601String(),
            updated_at: $announcement->updated_at?->toIso8601String(),
            attachments: array_values(
                $announcement->attachments
                    ->map(fn ($attachment): AnnouncementAttachmentData => AnnouncementAttachmentData::fromModel($attachment))
                    ->all(),
            ),
        );
    }
}
