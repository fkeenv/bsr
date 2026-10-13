<?php

namespace App\Actions\PlatformRoles;

use App\Data\AccessHistoryEntryData;
use App\Data\AccessHistoryOptionData;
use App\Data\PermissionOptionData;
use App\Models\User;
use App\Support\StaffPermissionCatalog;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

class ListAccessHistory
{
    public const ACTIONS = ['create' => 'Role created', 'rename' => 'Role renamed', 'delete' => 'Role deleted', 'permissions' => 'Permissions changed', 'assign' => 'Staff role assigned', 'revoke' => 'Staff role revoked', 'automatic-removal' => 'Staff roles automatically removed'];

    /**
     * @param  array{actor?:int|string|null,account?:int|string|null,role?:string|null,action?:string|null,occurred_from?:string|null,occurred_to?:string|null,page?:int|string|null}  $filters
     * @return array{entries:list<AccessHistoryEntryData>,pagination:array{current_page:int,last_page:int,total:int},filter_options:array<string,list<AccessHistoryOptionData>>,permission_labels:array<string,string>}
     */
    public function handle(User $actor, array $filters): array
    {
        abort_unless($actor->isSuperAdmin(), 403);
        $roles = DB::table('role_permission_changes')->select('id', 'action', 'actor_user_id', 'created_at')
            ->selectRaw("'role' as source, role_name, previous_role_name, new_role_name, previous_grants as previous_values, new_grants as new_values, null as user_id, null as reason");
        $assignments = DB::table('staff_role_assignment_changes')->select('id', 'action', 'actor_user_id', 'created_at')
            ->selectRaw("'assignment' as source, null as role_name, null as previous_role_name, null as new_role_name, previous_assignments as previous_values, new_assignments as new_values, user_id, reason");
        $memberships = $this->legacyRemovals()->select('membership.id')
            ->selectRaw("'automatic-removal' as action, membership.actor_user_id, membership.created_at, 'membership' as source, null as role_name, null as previous_role_name, null as new_role_name, membership.previous_state as previous_values, membership.new_state as new_values, membership.user_id, case when membership.action = 'end' then 'last-owner-membership-ended' else 'last-owner-membership-changed-to-resident' end as reason");
        if (! empty($filters['role'])) {
            $roles->where(fn (Builder $query) => $query->where('role_name', $filters['role'])->orWhere('previous_role_name', $filters['role'])->orWhere('new_role_name', $filters['role']));
            $assignments->where(fn (Builder $query) => $query->whereJsonContains('previous_assignments', $filters['role'])->orWhereJsonContains('new_assignments', $filters['role']));
            $memberships->whereJsonContains('membership.previous_state->staff_roles', $filters['role']);
        }
        $query = DB::query()->fromSub($roles->unionAll($assignments)->unionAll($memberships), 'history')
            ->leftJoin('users as actor', 'actor.id', '=', 'history.actor_user_id')->leftJoin('users as account', 'account.id', '=', 'history.user_id')
            ->select('history.*', 'actor.name as actor_name', 'account.name as account_name', 'account.email as account_email');
        foreach (['actor' => 'actor_user_id', 'account' => 'user_id', 'action' => 'action'] as $filter => $column) {
            if (! empty($filters[$filter])) {
                $query->where('history.'.$column, $filter === 'action' ? $filters[$filter] : (int) $filters[$filter]);
            }
        }
        if (! empty($filters['occurred_from'])) {
            $query->where('history.created_at', '>=', Carbon::parse($filters['occurred_from'], 'Asia/Manila')->startOfDay()->utc());
        }
        if (! empty($filters['occurred_to'])) {
            $query->where('history.created_at', '<=', Carbon::parse($filters['occurred_to'], 'Asia/Manila')->endOfDay()->utc());
        }
        $page = $query->orderByDesc('history.created_at')->orderBy('history.source')->orderByDesc('history.id')->paginate(25);
        $permissionLabels = [];
        foreach (StaffPermissionCatalog::permissions() as $permission) {
            $option = PermissionOptionData::fromPermission($permission);
            $permissionLabels[$option->value] = $option->feature.' · '.$option->label;
        }

        return ['entries' => array_values($page->getCollection()->map(fn (stdClass $row): AccessHistoryEntryData => AccessHistoryEntryData::fromRow($row))->all()),
            'pagination' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
            'filter_options' => $this->filterOptions(), 'permission_labels' => $permissionLabels];
    }

    /** @return array<string,list<AccessHistoryOptionData>> */
    private function filterOptions(): array
    {
        $actorIds = DB::table('role_permission_changes')->select('actor_user_id')->union(DB::table('staff_role_assignment_changes')->select('actor_user_id'))->union($this->legacyRemovals()->select('membership.actor_user_id'));
        $actors = User::query()->whereIn('id', $actorIds)->orderBy('name')->orderBy('id')->get(['id', 'name', 'email']);
        $accounts = User::query()->whereIn('id', DB::table('staff_role_assignment_changes')->select('user_id')->union($this->legacyRemovals()->select('membership.user_id')))->orderBy('name')->orderBy('id')->get(['id', 'name', 'email']);
        $names = DB::table('role_permission_changes')->select('role_name as name')
            ->union(DB::table('role_permission_changes')->select('previous_role_name as name')->whereNotNull('previous_role_name'))
            ->union(DB::table('role_permission_changes')->select('new_role_name as name')->whereNotNull('new_role_name'))
            ->union(DB::table('roles')->select('name')->where('guard_name', 'web'))->orderBy('name')->pluck('name');

        foreach ($this->legacyRemovals()->select('membership.previous_state')->distinct()->cursor() as $snapshot) {
            foreach (AccessHistoryEntryData::values($snapshot->previous_state, true) as $name) {
                $names->push($name);
            }
        }
        $names = $names->unique()->sort()->values();

        return ['actor' => array_values($actors->map(fn (User $user): AccessHistoryOptionData => new AccessHistoryOptionData((string) $user->id, $user->name.' ('.$user->email.')'))->all()),
            'account' => array_values($accounts->map(fn (User $user): AccessHistoryOptionData => new AccessHistoryOptionData((string) $user->id, $user->name.' ('.$user->email.')'))->all()),
            'role' => array_values($names->map(fn (string $name): AccessHistoryOptionData => new AccessHistoryOptionData($name, Str::headline($name)))->all()),
            'action' => array_map(fn (string $action, string $label): AccessHistoryOptionData => new AccessHistoryOptionData($action, $label), array_keys(self::ACTIONS), array_values(self::ACTIONS))];
    }

    private function legacyRemovals(): Builder
    {
        return DB::table('membership_changes as membership')
            ->whereIn('membership.action', ['end', 'change-role'])
            ->whereJsonLength('membership.previous_state->staff_roles', '>', 0)
            ->whereJsonLength('membership.new_state->staff_roles', 0)
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')->from('staff_role_assignment_changes as assignment')
                    ->where('assignment.action', 'automatic-removal')
                    ->whereColumn('assignment.membership_id', 'membership.membership_id')
                    ->whereColumn('assignment.actor_user_id', 'membership.actor_user_id')
                    ->whereColumn('assignment.user_id', 'membership.user_id')
                    ->whereColumn('assignment.created_at', '<=', 'membership.created_at');
            });
    }
}
