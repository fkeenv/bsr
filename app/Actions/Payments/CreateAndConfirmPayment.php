<?php

namespace App\Actions\Payments;

use App\Actions\Concerns\NormalizesMoneyAmount;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAndConfirmPayment
{
    use NormalizesMoneyAmount;

    public function __construct(private AllocatePaymentOnProperty $allocatePaymentOnProperty) {}

    /**
     * @param  array{property_id: int, amount: string|float|int, method: string, reference?: string|null, screenshot?: UploadedFile|null}  $data
     */
    public function handle(User $officer, array $data): Payment
    {
        $amount = $this->normalizeMoneyAmount($data['amount'], 'Payment amount');

        if ((float) $amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than ₱0.',
            ]);
        }

        $screenshotPath = null;

        if (array_key_exists('screenshot', $data) && $data['screenshot'] !== null) {
            $path = $data['screenshot']->store('payment-receipts', 'local');

            if ($path === false) {
                throw ValidationException::withMessages([
                    'screenshot' => 'The receipt screenshot could not be stored.',
                ]);
            }

            $screenshotPath = $path;
        }

        return DB::transaction(function () use ($officer, $data, $amount, $screenshotPath): Payment {
            $payment = Payment::query()->create([
                'property_id' => (int) $data['property_id'],
                'declared_by_user_id' => null,
                'amount' => $amount,
                'method' => PaymentMethod::from($data['method']),
                'reference' => $data['reference'] ?? null,
                'screenshot_path' => $screenshotPath,
                'status' => PaymentStatus::Confirmed,
                'confirmed_by_user_id' => $officer->id,
                'confirmed_at' => now(),
            ]);

            $property = $payment->property()->lockForUpdate()->firstOrFail();
            $this->allocatePaymentOnProperty->handle($payment, $property);

            return $payment->refresh();
        });
    }
}
