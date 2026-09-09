<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Suspends\CreateSuspend;
use App\Actions\Suspends\UpdateSuspend;
use App\Data\FeeTypeData;
use App\Data\SuspendData;
use App\Http\Requests\Officer\StoreSuspendRequest;
use App\Http\Requests\Officer\UpdateSuspendRequest;
use App\Models\FeeType;
use App\Models\Property;
use App\Models\Suspend;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class SuspendController
{
    public function index(): Response
    {
        $suspends = Suspend::query()
            ->with(['property', 'feeType'])
            ->orderByDesc('starts_year')
            ->orderByDesc('starts_month')
            ->orderBy('id')
            ->get()
            ->map(fn (Suspend $suspend): SuspendData => SuspendData::fromModel($suspend))
            ->values()
            ->all();

        return Inertia::render('officer/suspends/Index', [
            'suspends' => $suspends,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('officer/suspends/Create', [
            'properties' => $this->propertyOptions(),
            'feeTypes' => $this->feeTypeOptions(),
        ]);
    }

    public function store(StoreSuspendRequest $request, CreateSuspend $createSuspend): RedirectResponse
    {
        try {
            $createSuspend->handle($request->validated());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['ends_month' => $exception->getMessage()]);
        }

        return redirect()
            ->route('officer.suspends.index')
            ->with('success', 'Suspend created.');
    }

    public function edit(Suspend $suspend): Response
    {
        return Inertia::render('officer/suspends/Edit', [
            'suspend' => SuspendData::fromModel($suspend),
            'properties' => $this->propertyOptions(),
            'feeTypes' => $this->feeTypeOptions(),
        ]);
    }

    public function update(
        UpdateSuspendRequest $request,
        Suspend $suspend,
        UpdateSuspend $updateSuspend,
    ): RedirectResponse {
        try {
            $updateSuspend->handle($suspend, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['ends_month' => $exception->getMessage()]);
        }

        return redirect()
            ->route('officer.suspends.index')
            ->with('success', 'Suspend updated.');
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function propertyOptions(): array
    {
        return array_values(Property::query()
            ->orderBy('block')
            ->orderBy('lot')
            ->get()
            ->map(fn (Property $property): array => [
                'id' => $property->id,
                'label' => "Block {$property->block} · Lot {$property->lot}",
            ])
            ->all());
    }

    /**
     * @return list<FeeTypeData>
     */
    private function feeTypeOptions(): array
    {
        return array_values(FeeType::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (FeeType $feeType): FeeTypeData => FeeTypeData::fromModel($feeType))
            ->all());
    }
}
