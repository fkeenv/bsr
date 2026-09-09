<?php

namespace App\Data;

use App\Models\LegalDocumentVersion;
use Spatie\LaravelData\Data;

class LegalDocumentVersionData extends Data
{
    public function __construct(
        public int $id,
        public string $body,
        public string $published_at,
    ) {}

    public static function fromModel(LegalDocumentVersion $version): self
    {
        return new self(
            id: $version->id,
            body: $version->body,
            published_at: $version->published_at->toIso8601String(),
        );
    }
}
