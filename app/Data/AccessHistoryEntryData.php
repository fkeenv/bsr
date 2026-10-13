<?php

namespace App\Data;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use stdClass;
use UnexpectedValueException;

class AccessHistoryEntryData extends Data
{
    /**
     * @param  list<string>  $previous_values
     * @param  list<string>  $new_values
     */
    public function __construct(
        public string $id, public string $source, public string $action, public string $occurred_at,
        public string $origin, public ?int $actor_id, public string $actor_name,
        public ?int $account_id, public ?string $account_name, public ?string $account_email,
        public ?string $role_name, public ?string $previous_role_name, public ?string $new_role_name,
        public array $previous_values, public array $new_values, public ?string $reason,
    ) {}

    public static function fromRow(stdClass $row): self
    {
        return new self($row->source.'-'.$row->id, $row->source, $row->action, Carbon::parse($row->created_at, 'UTC')->toIso8601String(),
            $row->action === 'automatic-removal' ? 'automatic' : ($row->actor_name === null ? 'system' : 'user'),
            $row->actor_user_id, $row->actor_name ?? 'System', $row->user_id, $row->account_name, $row->account_email,
            $row->role_name, $row->previous_role_name ?? ($row->action === 'create' ? null : $row->role_name),
            $row->new_role_name ?? ($row->action === 'delete' ? null : $row->role_name),
            self::values($row->previous_values, $row->source === 'membership'), self::values($row->new_values, $row->source === 'membership'), $row->reason);
    }

    /** @return list<string> */
    public static function values(string $json, bool $membershipSnapshot = false): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if ($membershipSnapshot && is_array($decoded)) {
            $decoded = $decoded['staff_roles'] ?? null;
        }
        if (! is_array($decoded)) {
            throw new UnexpectedValueException('Audit values must be an array.');
        }
        $values = [];
        foreach ($decoded as $value) {
            if (! is_string($value)) {
                throw new UnexpectedValueException('Audit values must contain strings.');
            }
            $values[] = $value;
        }

        return $values;
    }
}
