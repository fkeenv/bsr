import type { StatementOfAccountPage } from '@/types/statement-of-account';

export type UnpaidRosterRow = {
    property_id: number;
    block: string;
    lot: string;
    label: string;
    recorded_owner_name: string | null;
    outstanding_balance: string;
    remaining_opening_balance: string;
    this_period_status: 'paid' | 'unpaid' | 'partial';
    oldest_open_label: string | null;
};

export type UnpaidRosterPage = {
    this_billing_period: {
        year: number;
        month: number;
        label: string;
        key: string;
    };
    roster_count: number;
    unpaid_count: number;
    filters_active: boolean;
    empty_state: 'clear' | 'no_matches' | null;
    rows: UnpaidRosterRow[];
    selected: StatementOfAccountPage | null;
    filter_options: {
        blocks: string[];
        owes_for: { value: string; label: string }[];
    };
    values: {
        block: string | null;
        lot: string | null;
        status: string | null;
        owes_for: string | null;
        property: number | null;
        charge: number | null;
    };
};
