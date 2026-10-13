<?php

namespace App\Actions\UserAccounts;

use App\Data\UserAccountData;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ListUserAccounts
{
    /**
     * @param  array{search?:string|null,staff_role?:string|null,membership_role?:string|null,live_membership?:string|null,registered_from?:string|null,registered_to?:string|null,page?:int|null}  $filters
     * @return array{accounts:array<int,UserAccountData>,pagination:array{current_page:int,last_page:int,total:int}}
     */
    public function handle(array $filters): array
    {
        $query = User::query()->with(['roles', 'memberships' => fn ($query) => $query->whereNull('ended_at')->with(['property', 'endedBy'])]);
        if (($search = trim($filters['search'] ?? '')) !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('mobile_number', 'like', $like)
                    ->orWhereHas('memberships', fn (Builder $query) => $query->whereNull('ended_at')->whereHas('property', fn (Builder $query) => $query->where('block', 'like', $like)->orWhere('lot', 'like', $like)));
            });
        }
        if (! empty($filters['staff_role'])) {
            $query->whereHas('roles', fn (Builder $query) => $query->where('name', $filters['staff_role'])->where('guard_name', 'web'));
        }
        if (! empty($filters['membership_role'])) {
            $query->whereHas('memberships', fn (Builder $query) => $query->whereNull('ended_at')->where('role', $filters['membership_role']));
        }
        if (($filters['live_membership'] ?? '') === 'yes') {
            $query->whereHas('memberships', fn (Builder $query) => $query->whereNull('ended_at'));
        }
        if (($filters['live_membership'] ?? '') === 'no') {
            $query->whereDoesntHave('memberships', fn (Builder $query) => $query->whereNull('ended_at'));
        }
        if (! empty($filters['registered_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['registered_from'], 'Asia/Manila')->startOfDay()->utc());
        }
        if (! empty($filters['registered_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['registered_to'], 'Asia/Manila')->endOfDay()->utc());
        }
        $page = $query->orderBy('name')->orderBy('id')->paginate(25);

        return ['accounts' => $page->getCollection()->map(fn (User $user): UserAccountData => UserAccountData::fromModel($user))->all(),
            'pagination' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]];
    }
}
