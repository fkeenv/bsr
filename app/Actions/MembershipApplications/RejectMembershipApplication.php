<?php

namespace App\Actions\MembershipApplications;

use App\Enums\MembershipApplicationStatus;
use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RejectMembershipApplication
{
    public function handle(MembershipApplication $application, User $reviewer): MembershipApplication
    {
        if ($application->status === MembershipApplicationStatus::Approved) {
            throw ValidationException::withMessages([
                'application' => 'An approved Membership Application cannot be rejected.',
            ]);
        }

        $application->fill([
            'status' => MembershipApplicationStatus::Rejected,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();

        return $application->fresh() ?? $application;
    }
}
