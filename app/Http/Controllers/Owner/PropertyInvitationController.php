<?php

namespace App\Http\Controllers\Owner;

use App\Actions\PropertyInvitations\AuthorizeOwnerPropertyInvitations;
use App\Actions\PropertyInvitations\CreatePropertyInvitation;
use App\Actions\PropertyInvitations\RevokePropertyInvitation;
use App\Data\PropertyInvitationData;
use App\Enums\MembershipRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\RevokePropertyInvitationRequest;
use App\Http\Requests\Owner\StorePropertyInvitationRequest;
use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Support\FlashToast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class PropertyInvitationController extends Controller
{
    public function index(Request $request, Property $property, AuthorizeOwnerPropertyInvitations $authorize): Response
    {
        $user = $request->user();
        assert($user !== null);
        $authorize->handle($user, $property)->authorize();

        $invitations = PropertyInvitation::query()
            ->whereBelongsTo($property)
            ->where('created_by_user_id', $user->id)
            ->with(['property', 'creator'])
            ->latest('id')
            ->get()
            ->map(fn (PropertyInvitation $invitation): PropertyInvitationData => PropertyInvitationData::fromModel($invitation))
            ->values()->all();

        return Inertia::render('owner/property-invitations/Index', [
            'property' => ['id' => $property->id, 'label' => 'Block '.$property->block.' · Lot '.$property->lot, 'is_active' => $property->is_active],
            'invitations' => $invitations,
        ]);
    }

    public function store(StorePropertyInvitationRequest $request, Property $property, CreatePropertyInvitation $create): JsonResponse
    {
        $user = $request->user();
        assert($user !== null);
        $issued = $create->handle($property, MembershipRole::Resident, $user);

        return response()->json([
            'url' => route('property-invitations.show', $issued->token),
            'code' => $issued->code,
        ], 201, ['Cache-Control' => 'private, no-store']);
    }

    public function destroy(RevokePropertyInvitationRequest $request, Property $property, PropertyInvitation $propertyInvitation, RevokePropertyInvitation $revoke): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);
        try {
            $revoke->handle($propertyInvitation, $user);
        } catch (InvalidArgumentException $exception) {
            FlashToast::error($exception->getMessage());

            return redirect()->route('owner.property-invitations.index', $property);
        }

        FlashToast::success('Invitation revoked.');

        return redirect()->route('owner.property-invitations.index', $property);
    }
}
