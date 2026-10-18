import type { AssociationEvent } from '@/types/event';
import type {
    EventRegistration,
    EventTicket,
} from '@/types/event-registration';

export function presentEventRegistration(registration: EventRegistration) {
    const cancelled = Boolean(
        registration.cancelled_at ||
        registration.status === 'cancelled' ||
        registration.event.status === 'cancelled',
    );
    const descriptions: Partial<Record<EventRegistration['status'], string>> = {
        confirmed:
            'Each attendee has an individual QR ticket. You can correct a name before check-in. Additional attendees need a new registration.',
        'awaiting-review':
            'Payment is awaiting staff review. Your places remain reserved. Tickets will appear after payment is confirmed.',
        expired:
            'The 24-hour payment window expired. Your places have been released. Start a new registration if places remain.',
        rejected:
            'Payment was rejected. These tickets are not valid for admission.',
    };

    return {
        label:
            registration.event.status === 'cancelled'
                ? 'Event cancelled'
                : registration.status_label,
        cancelled,
        variant: cancelled ? ('outline' as const) : ('secondary' as const),
        description: cancelled
            ? 'These tickets are invalid. Not valid for admission.'
            : (descriptions[registration.status] ?? null),
    };
}

export function presentEventTicket(
    ticket: EventTicket,
    event: AssociationEvent,
    registrationStatus?: EventRegistration['status'],
) {
    const cancelled =
        event.status === 'cancelled' || registrationStatus === 'cancelled';
    const valid = ticket.valid && !cancelled;
    const awaitingTicket =
        !cancelled &&
        !valid &&
        (registrationStatus === 'pending-payment' ||
            registrationStatus === 'awaiting-review');

    return {
        label:
            event.status === 'cancelled'
                ? 'Event cancelled'
                : cancelled
                  ? 'Registration cancelled'
                  : valid
                    ? ticket.checked_in_at
                        ? 'Checked in'
                        : 'Ready for admission'
                    : awaitingTicket
                      ? 'Ticket not issued yet'
                      : 'Invalid ticket',
        description:
            !valid && !awaitingTicket ? 'Not valid for admission' : null,
        history:
            ticket.checked_in_at && !valid ? 'Previously checked in' : null,
    };
}
