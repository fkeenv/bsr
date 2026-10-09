<?php

namespace App\Http\Controllers;

use App\Actions\PropertyInvitations\RedeemPropertyInvitation;
use App\Actions\PropertyInvitations\ShowPropertyInvitationRedemptionPage;
use App\Http\Requests\RedeemPropertyInvitationRequest;
use App\Support\FlashToast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class PropertyInvitationRedemptionController extends Controller
{
    public function show(Request $request, string $token, ShowPropertyInvitationRedemptionPage $showPage): Response
    {
        try {
            $page = Inertia::render('property-invitations/Show', [
                'token' => $token,
                'invitation' => $showPage->handle($token),
            ]);
            $status = Response::HTTP_OK;
        } catch (NotFoundHttpException|ServiceUnavailableHttpException $exception) {
            $page = Inertia::render('property-invitations/Unavailable', ['message' => $exception->getMessage()]);
            $status = $exception->getStatusCode();
        }

        $response = $page->toResponse($request);
        $response->setStatusCode($status);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
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
