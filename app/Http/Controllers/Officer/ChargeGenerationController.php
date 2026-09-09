<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Charges\GenerateChargesForPeriod;
use App\Http\Requests\Officer\GenerateChargesRequest;
use App\Models\AssociationSetting;
use App\Support\BillingPeriod;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChargeGenerationController
{
    public function create(): Response
    {
        $settings = AssociationSetting::current();

        return Inertia::render('officer/charges/Generate', [
            'levyDayOfMonth' => $settings->levy_day_of_month,
            'defaultYear' => now('Asia/Manila')->year,
            'defaultMonth' => now('Asia/Manila')->month,
        ]);
    }

    public function store(
        GenerateChargesRequest $request,
        GenerateChargesForPeriod $generateChargesForPeriod,
    ): RedirectResponse {
        $period = new BillingPeriod(
            (int) $request->validated('year'),
            (int) $request->validated('month'),
        );

        $created = $generateChargesForPeriod->handle($period);

        return redirect()
            ->route('officer.charges.generate.create')
            ->with('success', "Generated {$created} Charge(s) for {$period->label()}.");
    }
}
