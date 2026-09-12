export type Payment = {
    id: number;
    property_id: number;
    amount: string;
    method: string;
    reference: string | null;
    status: string;
    property_label: string;
    declared_by_name: string | null;
    rejection_reason: string | null;
    void_reason: string | null;
    confirmed_at: string | null;
    created_at: string | null;
    has_screenshot: boolean;
};
