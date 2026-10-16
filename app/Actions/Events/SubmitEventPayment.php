<?php

namespace App\Actions\Events;

use App\Enums\EventPaymentMethod;
use App\Enums\EventRegistrationStatus;
use App\Models\Event;
use App\Models\EventPaymentRecord;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\EventRegistrationAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubmitEventPayment
{
    public function __construct(private EventRegistrationAccess $access, private ExpireEventReservation $expire) {}

    /** @param array<string, mixed> $data */
    public function handle(User $account, EventRegistration $registration, array $data): EventPaymentRecord
    {
        $this->access->ensureOwner($registration, $account);
        $data = Validator::make($data, [
            'method' => ['required', Rule::enum(EventPaymentMethod::class)], 'reference' => ['required', 'string', 'max:1000'],
            'receipt' => ['nullable', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:10240'],
            'amount_centavos' => ['prohibited'], 'status' => ['prohibited'],
        ])->validate();
        $path = null;
        try {
            $payment = DB::transaction(function () use ($account, $registration, $data, &$path): ?EventPaymentRecord {
                $event = Event::query()->lockForUpdate()->findOrFail($registration->event_id);
                $registration = EventRegistration::query()->lockForUpdate()->findOrFail($registration->id);
                $this->access->ensureOwner($registration, $account);
                $existing = $registration->payment()->first();
                if ($existing !== null) {
                    if ($existing->method->value !== $data['method'] || $existing->reference !== trim($data['reference'])) {
                        throw ValidationException::withMessages(['reference' => 'Payment has already been submitted. Its original details cannot be changed.']);
                    }

                    return $existing;
                }
                if ($registration->status === EventRegistrationStatus::PendingPayment && $registration->expires_at?->lessThanOrEqualTo(now())) {
                    $this->expire->handle($registration);

                    return null;
                }
                if (! $event->status->isPublished() || $registration->status !== EventRegistrationStatus::PendingPayment || $registration->price_centavos <= 0) {
                    throw ValidationException::withMessages(['payment' => 'This registration is not awaiting payment.']);
                }
                if ($path === null && ($data['receipt'] ?? null) instanceof UploadedFile) {
                    $path = $data['receipt']->store('event-receipts', 'local');
                    if ($path === false) {
                        throw new \RuntimeException('The Event receipt could not be stored.');
                    }
                }
                $payment = $registration->payment()->create([
                    'amount_centavos' => $registration->total_centavos, 'method' => $data['method'],
                    'reference' => trim($data['reference']), 'receipt_path' => $path,
                ]);
                $registration->update(['status' => EventRegistrationStatus::AwaitingReview]);

                return $payment;
            }, 5);
        } catch (Throwable $exception) {
            if (is_string($path)) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
        if ($payment === null) {
            throw ValidationException::withMessages(['payment' => 'Your 24-hour reservation expired. Please start a new registration.']);
        }

        return $payment;
    }
}
