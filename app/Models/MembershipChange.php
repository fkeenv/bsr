<?php

namespace App\Models;

use Database\Factories\MembershipChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $membership_id
 * @property int $actor_user_id
 * @property int $user_id
 * @property string $action
 * @property array<string, mixed> $previous_state
 * @property array<string, mixed> $new_state
 */
#[Fillable(['membership_id', 'actor_user_id', 'user_id', 'action', 'previous_state', 'new_state'])]
class MembershipChange extends Model
{
    /** @use HasFactory<MembershipChangeFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['previous_state' => 'array', 'new_state' => 'array'];
    }
}
