<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Charges\UpdateChargeLines;
use App\Data\ChargeData;
use App\Data\FeeTypeData;
use App\Http\Requests\Officer\UpdateChargeRequest;
use App\Models\Charge;
use App\Models\FeeType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ChargeController
{
    public function index(): Response
    {
        $charges = Charge::query()
            ->with(['property', 'lines'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('id')
            ->get()
            ->map(fn (Charge $charge): ChargeData => ChargeData::fromModel($charge))
            ->values()
            ->all();

        return Inertia::render('officer/charges/Index', [
            'charges' => $charges,
        ]);
    }

    public function edit(Charge $charge): Response
    {
        return Inertia::render('officer/charges/Edit', [
            'charge' => ChargeData::fromModel($charge),
            'feeTypes' => FeeType::query()
                ->active()
                ->orderBy('name')
                ->get()
                ->map(fn (FeeType $feeType): FeeTypeData => FeeTypeData::fromModel($feeType))
                ->values()
                ->all(),
        ]);
    }

    public function update(
        UpdateChargeRequest $request,
        Charge $charge,
        UpdateChargeLines $updateChargeLines,
    ): RedirectResponse {
        try {
            $updateChargeLines->handle($charge, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('officer.charges.edit', $charge)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('officer.charges.edit', $charge)
            ->with('success', 'Charge updated.');
    }
}
