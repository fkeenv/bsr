<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMembershipOnboardingIsComplete
{
    /**
     * Redirect User Accounts that still need Membership Application onboarding.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->mustCompleteMembershipOnboarding()) {
            return redirect()->route('membership-application.create');
        }

        return $next($request);
    }
}
