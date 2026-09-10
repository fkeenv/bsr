<?php

namespace App\Data;

use App\Enums\PlatformRole;
use App\Models\User;
use Spatie\LaravelData\Data;

class PlatformRoleCandidateData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public bool $is_officer,
        public bool $is_administrator,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            is_officer: $user->hasRole(PlatformRole::Officer),
            is_administrator: $user->hasRole(PlatformRole::Administrator),
        );
    }
}
