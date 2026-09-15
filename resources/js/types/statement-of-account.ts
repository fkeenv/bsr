export type StatementPeriodPayment = {
    id: number;
    amount: string;
    method: string;
    reference: string | null;
    status: string;
    recorded_on: string | null;
};

export type StatementPeriodLine = {
    id: number;
    fee_type_id: number | null;
    fee_type_name: string;
    amount: string;
};

export type StatementPeriod = {
    charge_id: number;
    year: number;
    month: number;
    label: string;
    status: 'paid' | 'unpaid' | 'partial';
    remaining: string;
    charge_total: string;
    lines: StatementPeriodLine[];
    payments: StatementPeriodPayment[];
};

export type StatementPropertyOption = {
    property_id: number;
    label: string;
    outstanding_balance: string;
};

export type StatementOfAccountPage = {
    property: {
        id: number;
        label: string;
    };
    outstanding_balance: string;
    remaining_opening_balance: string;
    prepaid_balance: string;
    pending_declarations: import('@/types/payment').Payment[];
    periods: StatementPeriod[];
    selected_charge_id: number | null;
    selected_period: StatementPeriod | null;
    switcher: StatementPropertyOption[];
};
