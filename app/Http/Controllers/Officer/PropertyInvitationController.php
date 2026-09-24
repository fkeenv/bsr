<?php

namespace App\Http\Controllers\Officer;

use App\Actions\PropertyInvitations\CreatePropertyInvitation;
use App\Actions\PropertyInvitations\ListOfficerPropertyInvitationsPage;
use App\Actions\PropertyInvitations\RevokePropertyInvitation;
use App\Enums\MembershipRole;
use App\Http\Requests\Officer\RevokePropertyInvitationRequest;
use App\Http\Requests\Officer\StorePropertyInvitationRequest;
use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Support\FlashToast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PropertyInvitationController
{
    public function index(Request $request, ListOfficerPropertyInvitationsPage $listPage): Response
    {
        return Inertia::render('officer/property-invitations/Index', $listPage->handle(
            createdFrom: $request->string('created_from')->toString() ?: null,
            createdTo: $request->string('created_to')->toString() ?: null,
            expiresFrom: $request->string('expires_from')->toString() ?: null,
            expiresTo: $request->string('expires_to')->toString() ?: null,
        ));
    }

    public function store(
        StorePropertyInvitationRequest $request,
        CreatePropertyInvitation $createPropertyInvitation,
    ): JsonResponse {
        $creator = $request->user();
        assert($creator !== null);

        $property = Property::query()->findOrFail($request->integer('property_id'));
        $role = MembershipRole::from($request->validated('role'));
        $token = $createPropertyInvitation->handle($property, $role, $creator);

        return response()->json([
            'url' => url('/property-invitations/'.$token),
        ], HttpResponse::HTTP_CREATED);
    }

    public function destroy(
        RevokePropertyInvitationRequest $request,
        PropertyInvitation $propertyInvitation,
        RevokePropertyInvitation $revokePropertyInvitation,
    ): RedirectResponse {
        $revoker = $request->user();
        assert($revoker !== null);

        try {
            $revokePropertyInvitation->handle($propertyInvitation, $revoker);
        } catch (InvalidArgumentException $exception) {
            FlashToast::error($exception->getMessage());

            return redirect()->route('officer.property-invitations.index');
        }

        FlashToast::success('Invitation revoked.');

        return redirect()->route('officer.property-invitations.index');
    }
}
