<?php

namespace App\Enums;

enum EventPaymentMethod: string
{
    case Cash = 'cash';
    case GCash = 'gcash';
    case BankTransfer = 'bank-transfer';
}
