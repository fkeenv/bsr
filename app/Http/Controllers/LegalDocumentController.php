<?php

namespace App\Http\Controllers;

use App\Data\LegalDocumentVersionData;
use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentVersion;
use Inertia\Inertia;
use Inertia\Response;

class LegalDocumentController
{
    public function termsOfService(): Response
    {
        return $this->show(
            LegalDocumentType::TermsOfService,
            'legal/TermsOfService',
        );
    }

    public function privacyPolicy(): Response
    {
        return $this->show(
            LegalDocumentType::PrivacyPolicy,
            'legal/PrivacyPolicy',
        );
    }

    private function show(LegalDocumentType $type, string $component): Response
    {
        $version = LegalDocumentVersion::current($type);

        abort_if($version === null, 404);

        return Inertia::render($component, [
            'document' => LegalDocumentVersionData::fromModel($version),
        ]);
    }
}
