import type { AssociationEvent } from './event';

export type EventTicket = {
    id: number;
    name: string;
    valid: boolean;
    checked_in_at: string | null;
    ticket_url: string;
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
};
