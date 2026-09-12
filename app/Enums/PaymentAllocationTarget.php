<?php

namespace App\Enums;

enum PaymentAllocationTarget: string
{
    case OpeningBalance = 'opening_balance';
    case Charge = 'charge';
}
