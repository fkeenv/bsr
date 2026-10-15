export type EventAttendeeLookup = {
    id: number;
    name: string;
    account_name: string;
    status_label: string;
    valid: boolean;
    ticket: string | null;
    checked_in_at: string | null;
    checked_in_by_user_id: number | null;
};
export type EventAttendeeLookupPage = {
    data: EventAttendeeLookup[];
    current_page: number;
    last_page: number;
    total: number;
};
export type EventCheckInResult = {
    outcome: 'checked-in' | 'already-checked-in';
    name: string;
    ticket: string;
    checked_in_at: string;
    checked_in_by_user_id: number;
};
