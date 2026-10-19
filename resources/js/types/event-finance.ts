import type { DataTableFilterOption, DataTableValues } from './data-table';
import type { EventFinancial, EventRegistration } from './event-registration';

export type EventFinanceWork =
    | 'payment-review'
    | 'cancellation-review'
    | 'refund-due';
export type EventFinanceRow = {
    id: number;
    registration: EventRegistration;
    financial: EventFinancial;
    work: EventFinanceWork[];
    submission_key: string;
};
export type EventFinancePage = {
    registrations: EventFinanceRow[];
    canReviewPayments: boolean;
    canReviewRefunds: boolean;
    pagination: { current_page: number; last_page: number; total: number };
    workOptions: {
        value: 'attention' | 'all' | EventFinanceWork;
        label: string;
        count: number;
    }[];
    table: {
        searchables: string[];
        filters: string[];
        filterOptions: Record<string, DataTableFilterOption[]>;
        values: DataTableValues;
    };
};
