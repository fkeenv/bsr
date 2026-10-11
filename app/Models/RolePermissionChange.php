<?php

namespace App\Models;

use Database\Factories\RolePermissionChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $actor_user_id
 * @property int|null $role_id
 * @property string $role_name
 * @property list<string> $previous_grants
 * @property list<string> $new_grants
 */
#[Fillable(['actor_user_id', 'role_id', 'role_name', 'previous_grants', 'new_grants'])]
class RolePermissionChange extends Model
{
    /** @use HasFactory<RolePermissionChangeFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'previous_grants' => 'array',
            'new_grants' => 'array',
        ];
    }
}
