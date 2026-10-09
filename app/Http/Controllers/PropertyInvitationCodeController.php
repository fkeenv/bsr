<?php

namespace App\Http\Controllers;

use App\Actions\PropertyInvitations\ShowPropertyInvitationRedemptionPage;
use App\Http\Requests\LookupPropertyInvitationCodeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class PropertyInvitationCodeController extends Controller
{
    public function store(LookupPropertyInvitationCodeRequest $request, ShowPropertyInvitationRedemptionPage $showPage): RedirectResponse
    {
        $code = $request->string('code')->toString();
        try {
            $showPage->handle($code);
        } catch (NotFoundHttpException|ServiceUnavailableHttpException $exception) {
            throw ValidationException::withMessages(['code' => $exception->getMessage()]);
        }

        return redirect()->route('property-invitations.show', $code);
    }
}
