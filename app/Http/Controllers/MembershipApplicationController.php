<?php

namespace App\Http\Controllers;

use App\Actions\MembershipApplications\SubmitMembershipApplication;
use App\Data\LegalDocumentVersionData;
use App\Data\MembershipApplicationData;
use App\Enums\LegalDocumentType;
use App\Http\Requests\StoreMembershipApplicationRequest;
use App\Models\LegalDocumentVersion;
use App\Models\MembershipApplication;
use App\Models\Property;
use App\Support\FlashToast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MembershipApplicationController extends Controller
{
    public function create(): Response
    {
        $user = request()->user();
        assert($user !== null);

        $application = MembershipApplication::query()
            ->where('user_id', $user->id)
            ->editable()
            ->latest('id')
            ->with(['householdMembers', 'emergencyContacts', 'vehicles', 'property'])
            ->first();

        $terms = LegalDocumentVersion::current(LegalDocumentType::TermsOfService);
        $privacy = LegalDocumentVersion::current(LegalDocumentType::PrivacyPolicy);

        $propertyOptions = Property::query()
            ->active()
            ->orderBy('block')
            ->orderBy('lot')
            ->get()
            ->map(fn (Property $property): array => [
                'id' => $property->id,
                'label' => 'Block '.$property->block.' · Lot '.$property->lot,
            ])
            ->values()
            ->all();

        return Inertia::render('membership-application/Create', [
            'application' => $application !== null
                ? MembershipApplicationData::fromModel($application)
                : null,
            'propertyOptions' => $propertyOptions,
            'termsOfService' => $terms !== null ? LegalDocumentVersionData::fromModel($terms) : null,
            'privacyPolicy' => $privacy !== null ? LegalDocumentVersionData::fromModel($privacy) : null,
        ]);
    }

    public function store(
        StoreMembershipApplicationRequest $request,
        SubmitMembershipApplication $submitMembershipApplication,
    ): RedirectResponse {
        $user = $request->user();
        assert($user !== null);

        /** @var array{
         *     property_id: int,
         *     note?: string|null,
         *     accept_terms: mixed,
         *     accept_privacy: mixed,
         *     household_members?: list<array{name: string}>,
         *     emergency_contacts?: list<array{name: string, contact_number: string, relationship: string}>,
         *     vehicles?: list<array{year: int|string, make: string, model: string, plate: string, sticker_number: string}>,
         * } $validated
         */
        $validated = $request->validated();
        $validated['accept_terms'] = $request->boolean('accept_terms');
        $validated['accept_privacy'] = $request->boolean('accept_privacy');

        $application = $submitMembershipApplication->handle($user, $validated);

        FlashToast::success('Membership Application submitted.');

        if ($user->hasLiveMembership()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('membership-application.create');
    }
}
