<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Statements\BuildOfficerUnpaidRosterPage;
use App\Http\Requests\Officer\UnpaidRosterRequest;
use Inertia\Inertia;
use Inertia\Response;

class UnpaidRosterController
{
    public function index(
        UnpaidRosterRequest $request,
        BuildOfficerUnpaidRosterPage $buildPage,
    ): Response {
        $page = $buildPage->handle(
            block: $request->validated('block'),
            lot: $request->validated('lot'),
            status: $request->validated('status'),
            owesFor: $request->validated('owes_for'),
            selectedPropertyId: $request->validated('property') !== null
                ? (int) $request->validated('property')
                : null,
            selectedChargeId: $request->validated('charge') !== null
                ? (int) $request->validated('charge')
                : null,
        );

        return Inertia::render('officer/unpaid/Index', $page->toArray());
    }
}
