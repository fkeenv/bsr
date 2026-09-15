<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case GCash = 'gcash';
    case Maya = 'maya';
}
