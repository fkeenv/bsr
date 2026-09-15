<?php

namespace App\Enums;

enum PeriodStatus: string
{
    case Paid = 'paid';
    case Unpaid = 'unpaid';
    case Partial = 'partial';
}
