<?php

namespace App\Data;

use App\Models\AnnouncementAttachment;
use Spatie\LaravelData\Data;

class AnnouncementAttachmentData extends Data
{
    public function __construct(
        public int $id,
        public string $original_filename,
        public string $mime_type,
        public int $size,
        public bool $is_image,
        public bool $is_pdf,
        public string $url,
    ) {}

    public static function fromModel(AnnouncementAttachment $attachment): self
    {
        return new self(
            id: $attachment->id,
            original_filename: $attachment->original_filename,
            mime_type: $attachment->mime_type,
            size: $attachment->size,
            is_image: $attachment->isImage(),
            is_pdf: $attachment->isPdf(),
            url: route('announcements.attachments.show', $attachment),
        );
    }
}
