<?php

namespace App\Actions\LegalDocuments;

use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentVersion;
use App\Support\SanitizeHtml;
use InvalidArgumentException;

class PublishLegalDocument
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(LegalDocumentType $type, array $data): LegalDocumentVersion
    {
        $body = $data['body'] ?? null;

        if (! is_string($body) || SanitizeHtml::isBlank($body)) {
            throw new InvalidArgumentException($type->label().' body is required.');
        }

        return LegalDocumentVersion::query()->create([
            'type' => $type,
            'body' => SanitizeHtml::legalDocument($body),
            'published_at' => now(),
        ]);
    }
}
