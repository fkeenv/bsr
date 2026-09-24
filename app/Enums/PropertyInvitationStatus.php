<?php

namespace App\Enums;

enum PropertyInvitationStatus: string
{
    case Unused = 'unused';
    case Consumed = 'consumed';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
