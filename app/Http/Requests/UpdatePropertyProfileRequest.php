<?php

namespace App\Http\Requests;

use App\Actions\PropertyProfiles\AuthorizePropertyProfile;
use App\Concerns\HouseholdDetailsValidationRules;
use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\Response as AuthorizationResponse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyProfileRequest extends FormRequest
{
    use HouseholdDetailsValidationRules;

    public function authorize(AuthorizePropertyProfile $authorizePropertyProfile): AuthorizationResponse
    {
        $user = $this->user();
        $property = $this->route('property');

        if (! $user instanceof User || ! $property instanceof Property) {
            return AuthorizationResponse::denyAsNotFound();
        }

        return $authorizePropertyProfile->handle($user, $property);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->householdDetailsRules();
    }
}
