import type { AssociationEvent } from './event';

export type EventTicket = {
    id: number;
    name: string;
    valid: boolean;
    checked_in_at: string | null;
    ticket_url: string | null;
    qr_url: string | null;
};

export type EventRegistration = {
    id: number;
    account_name: string;
    event: AssociationEvent;
    attendee_count: number;
    confirmed_at: string | null;
    cancelled_at: string | null;
    tickets: EventTicket[];
    status:
        | 'pending-payment'
        | 'awaiting-review'
        | 'confirmed'
        | 'cancelled'
        | 'expired'
        | 'rejected';
    status_label: string;
    price_centavos: number;
    total_centavos: number;
    expires_at: string | null;
    payment_instructions: string | null;
};

export type EventRefund = {
    id: number;
    amount_centavos: number;
    method: string;
    reference: string;
    reason: string;
    recorded_by_user_id: number;
    refunded_at: string;
    recorded_at: string;
};
export type EventCancellation = {
    id: number;
    reason: string;
    status: 'pending' | 'approved' | 'declined';
    requested_at: string;
    resolved_at: string | null;
    resolved_by_user_id: number | null;
    resolution_reason: string | null;
};
export type EventFinancial = {
    payment: {
        id: number;
        amount_centavos: number;
        method: string;
        reference: string;
        status: 'submitted' | 'confirmed' | 'rejected';
        submitted_at: string;
        receipt_url: string | null;
        reviewed_at: string | null;
        reviewed_by_user_id: number | null;
        review_reason: string | null;
        refunded_centavos: number;
        refunds: EventRefund[];
    } | null;
    cancellations: EventCancellation[];
};
