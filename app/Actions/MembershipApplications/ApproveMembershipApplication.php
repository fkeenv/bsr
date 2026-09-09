<?php

namespace App\Actions\MembershipApplications;

use App\Actions\Memberships\SyncMemberPlatformRole;
use App\Enums\MembershipApplicationStatus;
use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveMembershipApplication
{
    public function __construct(private SyncMemberPlatformRole $syncMemberPlatformRole) {}

    public function handle(MembershipApplication $application, User $reviewer, MembershipRole $role): Membership
    {
        if ($application->status === MembershipApplicationStatus::Approved) {
            throw ValidationException::withMessages([
                'application' => 'This Membership Application is already approved.',
            ]);
        }

        return DB::transaction(function () use ($application, $reviewer, $role): Membership {
            $duplicate = Membership::query()
                ->live()
                ->where('user_id', $application->user_id)
                ->where('property_id', $application->property_id)
                ->lockForUpdate()
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'application' => 'A live Membership already exists for this person on this Property.',
                ]);
            }

            $application->fill([
                'status' => MembershipApplicationStatus::Approved,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $membership = Membership::query()->create([
                'user_id' => $application->user_id,
                'property_id' => $application->property_id,
                'membership_application_id' => $application->id,
                'role' => $role,
                'started_at' => now(),
            ]);

            $this->syncMemberPlatformRole->handle($application->user);

            return $membership;
        });
    }
}
