<?php

namespace App\Actions\Events;

use App\Enums\EventPaymentMethod;
use App\Enums\EventPermission;
use App\Mail\EventRegistrationUpdated;
use App\Models\Event;
use App\Models\EventPaymentRecord;
use App\Models\EventRefundRecord;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\PlatformAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecordEventRefund
{
    /** @param array<string, mixed> $data */
    public function handle(User $actor, EventPaymentRecord $payment, array $data): EventRefundRecord
    {
        abort_unless(PlatformAccess::allowsStaff($actor, EventPermission::RefundsReview), 403);
        $data = Validator::make($data, [
            'submission_key' => ['required', 'uuid'], 'amount_centavos' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::enum(EventPaymentMethod::class)], 'reference' => ['required', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:10000'], 'refunded_at' => ['required', 'date_format:Y-m-d\TH:i'],
        ])->validate();
        $refundedAt = Carbon::createFromFormat('!Y-m-d\TH:i', $data['refunded_at'], 'Asia/Manila')->utc();
        if ($refundedAt->isFuture()) {
            throw ValidationException::withMessages(['refunded_at' => 'Record an actual refund, not a future payment.']);
        }

        return DB::transaction(function () use ($actor, $payment, $data, $refundedAt): EventRefundRecord {
            Event::query()->lockForUpdate()->findOrFail($payment->registration->event_id);
            $registration = EventRegistration::query()->lockForUpdate()->findOrFail($payment->event_registration_id);
            $payment = EventPaymentRecord::query()->lockForUpdate()->findOrFail($payment->id);
            $previous = $payment->refunds()->where('submission_key', $data['submission_key'])->first();
            if ($previous !== null) {
                if ($previous->amount_centavos !== (int) $data['amount_centavos'] || $previous->method->value !== $data['method']
                    || $previous->reference !== trim($data['reference']) || $previous->reason !== trim($data['reason']) || ! $previous->refunded_at->equalTo($refundedAt)) {
                    throw ValidationException::withMessages(['refund' => 'This refund key was already used for different details.']);
                }

                return $previous;
            }
            if ($payment->status !== 'confirmed' || $registration->cancelled_at === null) {
                throw ValidationException::withMessages(['refund' => 'Refunds require a confirmed payment and a cancelled registration.']);
            }
            if ((int) $data['amount_centavos'] > $payment->amount_centavos - (int) $payment->refunds()->sum('amount_centavos')) {
                throw ValidationException::withMessages(['amount_centavos' => 'This exceeds the remaining refundable amount.']);
            }
            $refund = $payment->refunds()->create([
                'submission_key' => $data['submission_key'], 'amount_centavos' => (int) $data['amount_centavos'],
                'method' => $data['method'], 'reference' => trim($data['reference']), 'reason' => trim($data['reason']),
                'recorded_by_user_id' => $actor->id, 'refunded_at' => $refundedAt,
            ]);
            Mail::to($registration->user)->queue((new EventRegistrationUpdated($registration, 'An Event refund was recorded', 'PHP '.number_format($refund->amount_centavos / 100, 2).' was returned via '.$refund->method->value.'. Reference: '.$refund->reference.'. Reason: '.$refund->reason))->afterCommit());

            return $refund;
        }, 5);
    }
}
