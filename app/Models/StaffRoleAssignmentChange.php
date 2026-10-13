<?php

namespace App\Models;

use Database\Factories\StaffRoleAssignmentChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $actor_user_id
 * @property int $user_id
 * @property list<string> $previous_assignments
 * @property list<string> $new_assignments
 * @property string|null $reason
 */
#[Fillable(['actor_user_id', 'user_id', 'membership_id', 'action', 'previous_assignments', 'new_assignments', 'reason'])]
class StaffRoleAssignmentChange extends Model
{
    /** @use HasFactory<StaffRoleAssignmentChangeFactory> */
    use HasFactory;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['previous_assignments' => 'array', 'new_assignments' => 'array'];
    }
}
