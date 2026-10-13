import type { DataTableFilterOption } from './data-table';
export type AccessHistoryEntry = {
    id: string;
    source: 'role' | 'assignment' | 'membership';
    action: string;
    occurred_at: string;
    origin: 'user' | 'automatic' | 'system';
    actor_id: number | null;
    actor_name: string;
    account_id: number | null;
    account_name: string | null;
    account_email: string | null;
    role_name: string | null;
    previous_role_name: string | null;
    new_role_name: string | null;
    previous_values: string[];
    new_values: string[];
    reason: string | null;
};
export type AccessHistoryFilters = Record<
    'actor' | 'account' | 'role' | 'action',
    DataTableFilterOption[]
>;
