<?php

namespace App\Data;

use App\Models\Event;
use App\Support\SanitizeHtml;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

class EventData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public string $excerpt,
        public ?string $image_url,
        public string $venue,
        public string $starts_at,
        public string $ends_at,
        public string $registration_deadline,
        public string $audience,
        public string $audience_label,
        public string $status,
        public ?int $capacity,
        public int $price_centavos,
        public ?string $payment_instructions,
    ) {}

    public static function fromModel(Event $event, bool $includeDescription = true): self
    {
        return new self($event->id, $event->title, $includeDescription ? $event->description : '',
            Str::limit(SanitizeHtml::plainText($event->description), 180),
            $event->image_path !== null ? route('events.image', $event) : null, $event->venue,
            $event->starts_at->toIso8601String(), $event->ends_at->toIso8601String(),
            $event->registration_deadline->toIso8601String(), $event->audience->value, $event->audience->label(),
            $event->status->value, $event->capacity, $event->price_centavos, $includeDescription ? $event->payment_instructions : null);
    }
}
