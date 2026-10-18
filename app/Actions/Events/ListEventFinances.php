<?php

namespace App\Actions\Events;

use App\Data\EventFinanceRowData;
use App\Data\EventFinancialData;
use App\Data\EventRegistrationData;
use App\Enums\EventPermission;
use App\Enums\EventRegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\PlatformAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ListEventFinances
{
    private const WORK_LABELS = [
        'attention' => 'Needs attention',
        'payment-review' => 'Payment review',
        'cancellation-review' => 'Cancellation review',
        'refund-due' => 'Refund due',
        'all' => 'All registrations',
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     registrations: list<EventFinanceRowData>,
     *     pagination: array{current_page: int, last_page: int, total: int},
     *     workOptions: list<array{value: string, label: string, count: int}>,
     *     canReviewPayments: bool, canReviewRefunds: bool,
     *     table: array{
     *         searchables: list<string>, filters: list<string>,
     *         filterOptions: array{event: list<array{value: string, label: string}>},
     *         values: array{search: string|null, event: string|null, work: string}
     *     }
     * }
     */
    public function handle(User $actor, array $filters = []): array
    {
        $payments = PlatformAccess::allowsStaff($actor, EventPermission::PaymentsReview);
        $refunds = PlatformAccess::allowsStaff($actor, EventPermission::RefundsReview);
        abort_unless($payments || $refunds, 403);
        $filters = Validator::make($filters, [
            'work' => ['nullable', Rule::in(array_keys(self::WORK_LABELS))],
            'search' => ['nullable', 'string', 'max:255'],
            'event' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
        ])->validate();
        $work = $filters['work'] ?? 'attention';
        abort_if($work === 'payment-review' && ! $payments, 403);
        abort_if(in_array($work, ['cancellation-review', 'refund-due'], true) && ! $refunds, 403);
        $search = trim($filters['search'] ?? '');
        $query = EventRegistration::query()->where('price_centavos', '>', 0)
            ->when($filters['event'] ?? null, fn (Builder $query, $event) => $query->where('event_id', $event))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->whereHas('event', fn (Builder $event) => $event->where('title', 'like', '%'.$search.'%'))
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                    ->orWhereHas('payment', fn (Builder $payment) => $payment->where('reference', 'like', '%'.$search.'%'));
                if (ctype_digit($search)) {
                    $query->orWhere('id', $search);
                }
            }));
        $options = [];
        foreach (self::WORK_LABELS as $value => $label) {
            if (($value === 'payment-review' && ! $payments) || (in_array($value, ['cancellation-review', 'refund-due'], true) && ! $refunds)) {
                continue;
            }
            $count = $this->filterWork(clone $query, $value, $payments, $refunds)->count();
            $options[] = ['value' => $value, 'label' => $label, 'count' => $count];
        }
        $query = $this->filterWork($query, $work, $payments, $refunds)
            ->with(['event', 'user', 'payment.refunds', 'cancellationRequests'])->latest('id');
        $page = (int) ($filters['page'] ?? 1);
        $registrations = $query->paginate(25, ['*'], 'page', $page);
        if ($page > $registrations->lastPage()) {
            $registrations = $query->paginate(25, ['*'], 'page', $registrations->lastPage());
        }

        return [
            'registrations' => array_values($registrations->getCollection()->map(function (EventRegistration $registration) use ($payments, $refunds): EventFinanceRowData {
                $financial = EventFinancialData::fromModel($registration);
                $work = [];
                if ($payments && $financial->payment !== null && $financial->payment['status'] === 'submitted'
                    && in_array($registration->status, [EventRegistrationStatus::AwaitingReview, EventRegistrationStatus::Cancelled], true)) {
                    $work[] = 'payment-review';
                }
                if ($refunds && collect($financial->cancellations)->contains('status', 'pending')) {
                    $work[] = 'cancellation-review';
                }
                if ($refunds && ($financial->payment['refund_due_centavos'] ?? 0) > 0) {
                    $work[] = 'refund-due';
                }

                return new EventFinanceRowData($registration->id, EventRegistrationData::fromModel($registration, false), $financial, $work, Str::uuid()->toString());
            })->all()),
            'pagination' => ['current_page' => $registrations->currentPage(), 'last_page' => $registrations->lastPage(), 'total' => $registrations->total()],
            'workOptions' => $options,
            'canReviewPayments' => $payments,
            'canReviewRefunds' => $refunds,
            'table' => [
                'searchables' => ['event', 'account', 'reference', 'registration'], 'filters' => ['event'],
                'filterOptions' => ['event' => array_values(Event::query()->whereHas('registrations', fn (Builder $query) => $query->where('price_centavos', '>', 0))
                    ->orderBy('title')->orderBy('id')->get(['id', 'title'])->map(fn (Event $event): array => ['value' => (string) $event->id, 'label' => $event->title])->all())],
                'values' => ['search' => $search !== '' ? $search : null, 'event' => isset($filters['event']) ? (string) $filters['event'] : null, 'work' => $work],
            ],
        ];
    }

    /** @param Builder<EventRegistration> $query
     * @return Builder<EventRegistration>
     */
    private function filterWork(Builder $query, string $work, bool $payments, bool $refunds): Builder
    {
        if ($work === 'all') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($work, $payments, $refunds): void {
            if ($payments && in_array($work, ['attention', 'payment-review'], true)) {
                $query->orWhere(fn (Builder $query) => $query->whereIn('status', [EventRegistrationStatus::AwaitingReview, EventRegistrationStatus::Cancelled])
                    ->whereHas('payment', fn (Builder $payment) => $payment->where('status', 'submitted')));
            }
            if ($refunds && in_array($work, ['attention', 'cancellation-review'], true)) {
                $query->orWhereHas('cancellationRequests', fn (Builder $request) => $request->where('status', 'pending'));
            }
            if ($refunds && in_array($work, ['attention', 'refund-due'], true)) {
                $query->orWhere(fn (Builder $query) => $query->whereNotNull('cancelled_at')
                    ->whereHas('payment', fn (Builder $payment) => $payment->where('status', 'confirmed')
                        ->whereRaw('amount_centavos > (select coalesce(sum(amount_centavos), 0) from event_refund_records where event_payment_record_id = event_payment_records.id)')));
            }
        });
    }
}
