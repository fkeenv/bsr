<?php

namespace App\Support;

use App\Enums\EventAudience;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventDetails
{
    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:100000'],
            'venue' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
            'registration_deadline' => ['required', 'date_format:Y-m-d\TH:i', 'before_or_equal:starts_at'],
            'audience' => ['required', Rule::enum(EventAudience::class)],
            'capacity' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'payment_instructions' => ['nullable', 'string', 'max:10000'],
            'price_centavos' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'image' => ['nullable', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:10240'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validated(array $data): array
    {
        $data = Validator::make($data, self::rules())->validate();
        $data['capacity'] ??= null;
        $data['payment_instructions'] = isset($data['payment_instructions']) ? trim($data['payment_instructions']) : null;
        if ((int) $data['price_centavos'] > 0 && blank($data['payment_instructions'])) {
            throw ValidationException::withMessages(['payment_instructions' => 'Provide instructions for external Event payment.']);
        }
        $data['title'] = trim($data['title']);
        $data['venue'] = trim($data['venue']);
        $data['description'] = SanitizeHtml::richText($data['description']);
        if (SanitizeHtml::isBlank($data['description'])) {
            throw ValidationException::withMessages(['description' => 'An Event description is required.']);
        }
        foreach (['starts_at', 'ends_at', 'registration_deadline'] as $field) {
            $data[$field] = Carbon::createFromFormat('!Y-m-d\TH:i', $data[$field], 'Asia/Manila')->utc();
        }

        return $data;
    }
}
