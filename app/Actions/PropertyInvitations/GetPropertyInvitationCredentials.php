<?php

namespace App\Actions\PropertyInvitations;

use App\Data\IssuedPropertyInvitationData;
use App\Models\PropertyInvitation;
use InvalidArgumentException;

class GetPropertyInvitationCredentials
{
    public function handle(PropertyInvitation $invitation): IssuedPropertyInvitationData
    {
        $invitation->refresh()->load('property');
        $reason = $invitation->sharingUnavailableReason();
        if ($reason !== null) {
            throw new InvalidArgumentException($reason);
        }

        $token = $invitation->share_token;
        $code = $invitation->share_code;
        assert($token !== null && $code !== null);

        return new IssuedPropertyInvitationData($token, $code);
    }
}
