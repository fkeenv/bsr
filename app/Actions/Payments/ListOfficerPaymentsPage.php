<?php

namespace App\Actions\Payments;

use App\Data\PaymentData;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;

class ListOfficerPaymentsPage
{
    /**
     * @return array{
     *     payments: list<PaymentData>,
     *     table: array{
     *         searchables: list<string>,
     *         filters: list<string>,
     *         filterOptions: object,
     *         values: array{search: string|null, status: string|null}
     *     }
     * }
     */
    public function handle(?string $search = null, ?string $status = null): array
    {
        $paymentsQuery = Payment::query()
            ->with(['property', 'declaredBy'])
            ->when(
                filled($status),
                fn ($query) => $query->where('status', $status),
            )
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    $propertyIds = Property::query()
                        ->search($search, ['block', 'lot', 'street_address'])
                        ->pluck('id');

                    $declarerIds = User::query()
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->pluck('id');

                    $query->where(function ($nested) use ($search, $propertyIds, $declarerIds): void {
                        $nested->where('reference', 'like', '%'.$search.'%')
                            ->orWhereIn('property_id', $propertyIds)
                            ->orWhereIn('declared_by_user_id', $declarerIds);
                    });
                },
            )
            ->orderByRaw('case when status = ? then 0 else 1 end', [PaymentStatus::Pending->value])
            ->latest('id')
            ->limit(100)
            ->get();

        /** @var list<PaymentData> $payments */
        $payments = $paymentsQuery
            ->map(fn (Payment $payment): PaymentData => PaymentData::fromModel($payment))
            ->values()
            ->all();

        /** @var list<array{value: string, label: string}> $statusOptions */
        $statusOptions = collect(PaymentStatus::cases())
            ->map(fn (PaymentStatus $case): array => [
                'value' => $case->value,
                'label' => ucfirst($case->value),
            ])
            ->values()
            ->all();

        return [
            'payments' => $payments,
            'table' => [
                'searchables' => ['reference', 'property', 'declarer'],
                'filters' => ['status'],
                'filterOptions' => (object) [
                    'status' => $statusOptions,
                ],
                'values' => [
                    'search' => $search,
                    'status' => $status,
                ],
            ],
        ];
    }
}
