<?php

namespace App\Http\Requests\Owner;

use App\Actions\PropertyInvitations\AuthorizeOwnerPropertyInvitations;
use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

class StorePropertyInvitationRequest extends FormRequest
{
    public function authorize(AuthorizeOwnerPropertyInvitations $authorize): Response
    {
        $user = $this->user();
        $property = $this->route('property');
        if (! $user instanceof User || ! $property instanceof Property || ! $property->is_active) {
            return Response::denyAsNotFound();
        }

        return $authorize->handle($user, $property);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['role' => ['sometimes', 'in:resident'], 'property_id' => ['prohibited']];
    }
}
