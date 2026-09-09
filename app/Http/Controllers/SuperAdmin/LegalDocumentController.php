<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\LegalDocuments\PublishLegalDocument;
use App\Data\LegalDocumentVersionData;
use App\Enums\LegalDocumentType;
use App\Http\Requests\SuperAdmin\PublishLegalDocumentRequest;
use App\Models\LegalDocumentVersion;
use App\Support\FlashToast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LegalDocumentController
{
    public function editTermsOfService(): Response
    {
        return $this->edit(
            LegalDocumentType::TermsOfService,
            'super-admin/legal/EditTermsOfService',
        );
    }

    public function updateTermsOfService(
        PublishLegalDocumentRequest $request,
        PublishLegalDocument $publishLegalDocument,
    ): RedirectResponse {
        return $this->update(
            LegalDocumentType::TermsOfService,
            $request,
            $publishLegalDocument,
            'super-admin.terms-of-service.edit',
        );
    }

    public function editPrivacyPolicy(): Response
    {
        return $this->edit(
            LegalDocumentType::PrivacyPolicy,
            'super-admin/legal/EditPrivacyPolicy',
        );
    }

    public function updatePrivacyPolicy(
        PublishLegalDocumentRequest $request,
        PublishLegalDocument $publishLegalDocument,
    ): RedirectResponse {
        return $this->update(
            LegalDocumentType::PrivacyPolicy,
            $request,
            $publishLegalDocument,
            'super-admin.privacy-policy.edit',
        );
    }

    private function edit(LegalDocumentType $type, string $component): Response
    {
        $version = LegalDocumentVersion::current($type);

        return Inertia::render($component, [
            'document' => $version === null
                ? null
                : LegalDocumentVersionData::fromModel($version),
        ]);
    }

    private function update(
        LegalDocumentType $type,
        PublishLegalDocumentRequest $request,
        PublishLegalDocument $publishLegalDocument,
        string $redirectRoute,
    ): RedirectResponse {
        $publishLegalDocument->handle($type, $request->validated());

        FlashToast::success($type->label().' published.');

        return redirect()->route($redirectRoute);
    }
}
