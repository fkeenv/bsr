<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Properties\ActivateProperty;
use App\Actions\Properties\CreateProperty;
use App\Actions\Properties\DeactivateProperty;
use App\Actions\Properties\DestroyProperty;
use App\Actions\Properties\UpdateProperty;
use App\Data\PropertyData;
use App\Http\Requests\Officer\StorePropertyRequest;
use App\Http\Requests\Officer\UpdatePropertyRequest;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class PropertyController
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;
        $block = $request->string('block')->toString() ?: null;

        $searchColumns = [
            'recorded_owner_name',
            'block',
            'lot',
            'street_address',
        ];

        $properties = Property::query()
            ->search($search, $searchColumns)
            ->filterByStatus($status)
            ->filterByBlockLot($block)
            ->orderBy('block')
            ->orderBy('lot')
            ->get()
            ->map(fn (Property $property): PropertyData => PropertyData::fromModel($property))
            ->values()
            ->all();

        return Inertia::render('officer/properties/Index', [
            'properties' => $properties,
            'table' => [
                'searchables' => ['name', 'block', 'lot'],
                'filters' => ['status', 'block'],
                'filterOptions' => [
                    'status' => [
                        ['value' => 'active', 'label' => 'Active'],
                        ['value' => 'inactive', 'label' => 'Inactive'],
                    ],
                    'block' => $this->columnOptions('block'),
                ],
                'values' => [
                    'search' => $search,
                    'status' => $status,
                    'block' => $block,
                ],
            ],
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function columnOptions(string $column): array
    {
        return Property::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy($column)
            ->distinct()
            ->pluck($column)
            ->map(fn (mixed $value): array => [
                'value' => (string) $value,
                'label' => (string) $value,
            ])
            ->values()
            ->all();
    }

    public function create(): Response
    {
        return Inertia::render('officer/properties/Create');
    }

    public function store(StorePropertyRequest $request, CreateProperty $createProperty): RedirectResponse
    {
        $createProperty->handle($request->validated());

        return redirect()
            ->route('officer.properties.index')
            ->with('success', 'Property created.');
    }

    public function edit(Property $property): Response
    {
        return Inertia::render('officer/properties/Edit', [
            'property' => PropertyData::fromModel($property),
        ]);
    }

    public function update(
        UpdatePropertyRequest $request,
        Property $property,
        UpdateProperty $updateProperty,
    ): RedirectResponse {
        $updateProperty->handle($property, $request->validated());

        return redirect()
            ->route('officer.properties.index')
            ->with('success', 'Property updated.');
    }

    public function destroy(Property $property, DestroyProperty $destroyProperty): RedirectResponse
    {
        try {
            $destroyProperty->handle($property);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('officer.properties.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('officer.properties.index')
            ->with('success', 'Property deleted.');
    }

    public function deactivate(Property $property, DeactivateProperty $deactivateProperty): RedirectResponse
    {
        $deactivateProperty->handle($property);

        return redirect()
            ->route('officer.properties.index')
            ->with('success', 'Property marked inactive.');
    }

    public function activate(Property $property, ActivateProperty $activateProperty): RedirectResponse
    {
        $activateProperty->handle($property);

        return redirect()
            ->route('officer.properties.index')
            ->with('success', 'Property reactivated.');
    }
}
