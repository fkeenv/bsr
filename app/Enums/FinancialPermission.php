<?php

namespace App\Enums;

enum FinancialPermission: string
{
    case PaymentsRead = 'payments.read';
    case PaymentsCreate = 'payments.create';
    case PaymentsConfirm = 'payments.confirm';
    case PaymentsReject = 'payments.reject';
    case PaymentsVoid = 'payments.void';
    case ChargesRead = 'charges.read';
    case ChargesEdit = 'charges.edit';
    case ChargesGenerate = 'charges.generate';
    case UnpaidRead = 'unpaid.read';
    case PrintedBillsSingle = 'printed-bills.single';
    case PrintedBillsBatch = 'printed-bills.batch';
    case FeeTypesRead = 'fee-types.read';
    case FeeTypesCreate = 'fee-types.create';
    case FeeTypesEdit = 'fee-types.edit';
    case LevySettingsRead = 'levy-settings.read';
    case LevySettingsEdit = 'levy-settings.edit';
    case BillSettingsRead = 'bill-settings.read';
    case BillSettingsEdit = 'bill-settings.edit';

    public function feature(): string
    {
        return match ($this) {
            self::PaymentsRead => 'Payments',
            self::PaymentsCreate => 'Payments',
            self::PaymentsConfirm => 'Payments',
            self::PaymentsReject => 'Payments',
            self::PaymentsVoid => 'Payments',
            self::ChargesRead => 'Charges',
            self::ChargesEdit => 'Charges',
            self::ChargesGenerate => 'Charges',
            self::UnpaidRead => 'Unpaid records',
            self::PrintedBillsSingle => 'Printed Bills',
            self::PrintedBillsBatch => 'Printed Bills',
            self::FeeTypesRead => 'Fee Types',
            self::FeeTypesCreate => 'Fee Types',
            self::FeeTypesEdit => 'Fee Types',
            self::LevySettingsRead => 'Levy settings',
            self::LevySettingsEdit => 'Levy settings',
            self::BillSettingsRead => 'Bill settings',
            self::BillSettingsEdit => 'Bill settings',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PaymentsRead => 'List Payments',
            self::PaymentsCreate => 'Record confirmed Payments',
            self::PaymentsConfirm => 'Confirm Payments',
            self::PaymentsReject => 'Reject Payments',
            self::PaymentsVoid => 'Void Payments',
            self::ChargesRead => 'List Charges',
            self::ChargesEdit => 'Edit Charges',
            self::ChargesGenerate => 'Generate Charges',
            self::UnpaidRead => 'Read unpaid records',
            self::PrintedBillsSingle => 'Download a Printed Bill',
            self::PrintedBillsBatch => 'Download batch Printed Bills',
            self::FeeTypesRead => 'List Fee Types',
            self::FeeTypesCreate => 'Create Fee Types',
            self::FeeTypesEdit => 'Edit Fee Types',
            self::LevySettingsRead => 'Read levy settings',
            self::LevySettingsEdit => 'Edit levy settings',
            self::BillSettingsRead => 'Read Printed Bill settings',
            self::BillSettingsEdit => 'Edit Printed Bill settings',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PaymentsRead => 'Read Payment declarations and their status.',
            self::PaymentsCreate => 'Record and confirm a Payment without a Member declaration; allocation runs immediately.',
            self::PaymentsConfirm => 'Confirm pending declarations and allocate them to Property debt.',
            self::PaymentsReject => 'Reject pending declarations with a reason.',
            self::PaymentsVoid => 'Void confirmed Payments with a reason and reverse their allocation.',
            self::ChargesRead => 'Read Charges across the association.',
            self::ChargesEdit => 'Update Charge lines while financial history allows editing.',
            self::ChargesGenerate => 'Create missing Charges for a Billing Period without rewriting existing Charges.',
            self::UnpaidRead => 'View Outstanding Balances and Property statements for collection follow-up.',
            self::PrintedBillsSingle => 'Generate a single Property Printed Bill for a Billing Period.',
            self::PrintedBillsBatch => 'Generate Printed Bills for unpaid Properties in a Billing Period.',
            self::FeeTypesRead => 'Read the association fee schedule.',
            self::FeeTypesCreate => 'Add a named fee and its amount to the schedule.',
            self::FeeTypesEdit => 'Change a fee name or amount without rewriting past Charges.',
            self::LevySettingsRead => 'View the scheduled day of the monthly levy.',
            self::LevySettingsEdit => 'Change the scheduled levy day within the existing calendar rules.',
            self::BillSettingsRead => 'View the letterhead and payment channels used on Printed Bills.',
            self::BillSettingsEdit => 'Update the letterhead and payment channels used on Printed Bills.',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
