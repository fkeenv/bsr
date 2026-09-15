<?php

namespace App\Http\Controllers;

use App\Actions\Statements\BuildMemberStatementOfAccountPage;
use App\Models\Membership;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatementOfAccountController extends Controller
{
    public function show(
        Request $request,
        Property $property,
        BuildMemberStatementOfAccountPage $buildPage,
    ): Response {
        $user = $request->user();
        assert($user !== null);

        $selectedChargeId = $request->integer('charge') ?: null;

        $page = $buildPage->handle($user, $property, $selectedChargeId);

        return Inertia::render('statement-of-account/Show', $page->toArray());
    }

    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $propertyId = Membership::query()
            ->live()
            ->where('user_id', $user->id)
            ->orderBy('property_id')
            ->value('property_id');

        if ($propertyId === null) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('statement-of-account.show', $propertyId);
    }
}
