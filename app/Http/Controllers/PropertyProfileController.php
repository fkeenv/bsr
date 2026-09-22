<?php

namespace App\Http\Controllers;

use App\Actions\PropertyProfiles\AuthorizePropertyProfile;
use App\Actions\PropertyProfiles\UpdatePropertyProfile;
use App\Data\PropertyProfileData;
use App\Http\Requests\UpdatePropertyProfileRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropertyProfileController extends Controller
{
    public function edit(
        Request $request,
        Property $property,
        AuthorizePropertyProfile $authorizePropertyProfile,
    ): Response {
        $user = $request->user();
        assert($user instanceof User);

        $authorizePropertyProfile->handle($user, $property)->authorize();

        return Inertia::render('property-profile/Edit', [
            'profile' => PropertyProfileData::fromProperty($property),
            'canAccessOfficerProperties' => $user->canAccessOfficerSurfaces(),
        ]);
    }

    public function update(
        UpdatePropertyProfileRequest $request,
        Property $property,
        UpdatePropertyProfile $updatePropertyProfile,
    ): RedirectResponse {
        $updatePropertyProfile->handle($property, $request->validated());

        return back()->with('success', 'Property profile saved.');
    }
}
