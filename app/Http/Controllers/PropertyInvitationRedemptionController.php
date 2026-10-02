<?php

namespace App\Http\Controllers;

use App\Actions\PropertyInvitations\RedeemPropertyInvitation;
use App\Actions\PropertyInvitations\ShowPropertyInvitationRedemptionPage;
use App\Http\Requests\RedeemPropertyInvitationRequest;
use App\Support\FlashToast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PropertyInvitationRedemptionController extends Controller
{
    public function show(string $token, ShowPropertyInvitationRedemptionPage $showPage): Response
    {
        return Inertia::render('property-invitations/Show', [
            'token' => $token,
            'invitation' => $showPage->handle($token),
        ]);
    }

    public function store(
        RedeemPropertyInvitationRequest $request,
        string $token,
        RedeemPropertyInvitation $redeemPropertyInvitation,
    ): RedirectResponse {
        $recipient = $request->user();
        assert($recipient !== null);

        $membership = $redeemPropertyInvitation->handle(
            token: $token,
            recipient: $recipient,
            termsOfServiceVersionId: $request->integer('terms_of_service_version_id'),
            privacyPolicyVersionId: $request->integer('privacy_policy_version_id'),
        );

        $membership->load('property');
        FlashToast::success(
            'Your Membership for Block '.$membership->property->block.' · Lot '.$membership->property->lot.' is active.',
        );

        return redirect()->route('dashboard');
    }
}
