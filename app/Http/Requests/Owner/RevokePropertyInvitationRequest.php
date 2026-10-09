<?php

namespace App\Http\Requests\Owner;

use App\Actions\PropertyInvitations\AuthorizeOwnerPropertyInvitations;
use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

class RevokePropertyInvitationRequest extends FormRequest
{
    public function authorize(AuthorizeOwnerPropertyInvitations $authorize): Response
    {
        $user = $this->user();
        $property = $this->route('property');
        if (! $user instanceof User || ! $property instanceof Property) {
            return Response::denyAsNotFound();
        }
        $invitation = $this->route('propertyInvitation');
        if (! $invitation instanceof PropertyInvitation || $invitation->property_id !== $property->id || $invitation->created_by_user_id !== $user->id) {
            return Response::denyAsNotFound();
        }

        return $authorize->handle($user, $property);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
