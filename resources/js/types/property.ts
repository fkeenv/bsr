export type Property = {
    id: number;
    block: string;
    lot: string;
    street_address: string | null;
    recorded_owner_name: string | null;
    opening_balance: string;
    opening_balance_is_frozen: boolean;
    is_active: boolean;
    has_been_charged: boolean;
};
