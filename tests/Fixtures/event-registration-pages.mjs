import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { mount } from './mount-vue.mjs';

const event = {
    id: 7,
    title: 'Community afternoon',
    description: '<p>Come together.</p>',
    venue: 'Clubhouse',
    starts_at: '2027-01-20T08:00:00Z',
    ends_at: '2027-01-20T10:00:00Z',
    registration_deadline: '2027-01-19T04:00:00Z',
    audience_label: 'Public',
    capacity: 10,
    status: 'published',
    price_centavos: 0,
};
const ticket = {
    id: 8,
    name: 'Maria Santos',
    valid: true,
    checked_in_at: null,
    ticket_url: '/event-tickets/opaque',
    qr_url: '/event-tickets/opaque/qr',
};
const registration = {
    id: 3,
    account_name: 'Maria Santos',
    event,
    attendee_count: 1,
    confirmed_at: '2026-10-14T00:00:00Z',
    cancelled_at: null,
    tickets: [ticket],
    status: 'confirmed',
    status_label: 'Confirmed',
    price_centavos: 0,
    total_centavos: 0,
    expires_at: null,
    payment_instructions: null,
};

void test('cancelled Events explain invalid admission without erasing previous check-in on either ticket surface', (context) => {
    const cancelledEvent = { ...event, status: 'cancelled' };
    const checkedIn = {
        ...ticket,
        valid: false,
        qr_url: null,
        checked_in_at: '2027-01-20T08:00:00Z',
    };
    for (const { file, props } of [
        {
            file: 'event-registrations',
            props: {
                registration: {
                    ...registration,
                    event: cancelledEvent,
                    tickets: [checkedIn],
                },
                isOwner: false,
                financial: null,
                submissionKey: 'unused',
            },
        },
        {
            file: 'event-tickets',
            props: {
                event: cancelledEvent,
                ticket: checkedIn,
                registrationId: 3,
            },
        },
    ]) {
        const ui = mount(`resources/js/pages/${file}/Show.vue`, props);
        context.after(ui.unmount);
        assert.match(ui.text(), /Event cancelled/);
        assert.match(ui.text(), /Not valid for admission/);
        assert.match(ui.text(), /Previously checked in/);
        assert.equal(ui.all('img').length, 0);
        assert.equal(
            ui
                .all('Button')
                .some((button) => /Print ticket/.test(ui.text(button))),
            false,
        );
    }
});

void test('ticket presentation preserves reservation states and uses server admission validity', (context) => {
    for (const example of [
        {
            status: 'pending-payment',
            valid: false,
            label: 'Ticket not issued yet',
        },
        {
            status: 'awaiting-review',
            valid: false,
            label: 'Ticket not issued yet',
        },
        { status: 'expired', valid: false, label: 'Invalid ticket' },
        { status: 'rejected', valid: false, label: 'Invalid ticket' },
        { status: 'cancelled', valid: false, label: 'Registration cancelled' },
        { status: 'confirmed', valid: false, label: 'Invalid ticket' },
        { status: 'confirmed', valid: true, label: 'Ready for admission' },
    ]) {
        const ui = mount('resources/js/components/EventTicketStatus.vue', {
            ticket: { ...ticket, valid: example.valid },
            event: { ...event, status: 'rescheduled' },
            registrationStatus: example.status,
        });
        context.after(ui.unmount);
        assert.equal(ui.text(ui.all('Badge')[0]).trim(), example.label);
        assert.equal(
            ui.text().includes('Not valid for admission'),
            !example.valid &&
                !['pending-payment', 'awaiting-review'].includes(
                    example.status,
                ),
        );
    }
});
const Form = vue.defineComponent({
    inheritAttrs: false,
    setup:
        (_, { attrs, slots }) =>
        () =>
            vue.h(
                'Form',
                attrs,
                slots.default?.({ errors: {}, processing: false }),
            ),
});

void test('the registration form adds and removes named attendees while keeping the submission identity', async (context) => {
    const ui = mount(
        'resources/js/pages/events/Show.vue',
        {
            event,
            registrationAvailability: null,
            submissionKey: 'stable-submission',
        },
        null,
        '/',
        {
            '@inertiajs/vue3': {
                Form,
                usePage: () => ({
                    props: { auth: { user: { name: 'Maria Santos' } } },
                }),
            },
        },
    );
    context.after(ui.unmount);
    const attendees = () => ui.all('Input');
    assert.equal(attendees().length, 1);
    assert.equal(attendees()[0].props.modelValue, 'Maria Santos');
    assert.equal(ui.all('input')[0].props.value, 'stable-submission');
    assert.equal(ui.all('Form')[0].props.action, '/events/7/registrations');
    ui.all('Button')
        .find((node) => ui.text(node).trim() === 'Add attendee')
        .props.onClick();
    await vue.nextTick();
    assert.equal(attendees().length, 2);
    assert.equal(attendees()[1].props.name, 'attendees[1][name]');
    attendees()[1].props['onUpdate:modelValue']('Jose Santos');
    await vue.nextTick();
    assert.equal(attendees()[1].props.modelValue, 'Jose Santos');
    const removes = ui
        .all('Button')
        .filter((node) => ui.text(node).trim() === 'Remove');
    removes[1].props.onClick();
    await vue.nextTick();
    assert.equal(attendees().length, 1);
    assert.equal(ui.all('input')[0].props.value, 'stable-submission');
});

void test('unavailable registration shows the reason and guests receive a sign in link', (context) => {
    const ui = mount('resources/js/pages/events/Show.vue', {
        event,
        registrationAvailability: 'Sign in to register for this Event.',
        submissionKey: null,
    });
    context.after(ui.unmount);
    assert.equal(ui.all('Form').length, 0);
    assert.match(ui.text(), /Sign in to register/);
    assert.ok(ui.all('Link').some((node) => node.props.href.url === '/login'));
});

void test('registration owners see correction and whole cancellation forms but staff readers do not', (context) => {
    const owner = mount(
        'resources/js/pages/event-registrations/Show.vue',
        {
            registration,
            isOwner: true,
            financial: null,
            submissionKey: 'unused',
        },
        null,
        '/',
        { '@inertiajs/vue3': { Form } },
    );
    context.after(owner.unmount);
    assert.equal(owner.all('Form').length, 2);
    assert.ok(
        owner
            .all('Form')
            .some((node) => node.props.action.includes('/event-attendees/8')),
    );
    assert.ok(
        owner
            .all('Form')
            .some(
                (node) => node.props.action === '/event-registrations/3/cancel',
            ),
    );
    assert.match(owner.text(), /All 1 places will be released/);
    const staff = mount('resources/js/pages/event-registrations/Show.vue', {
        registration,
        isOwner: false,
        financial: null,
        submissionKey: 'unused',
    });
    context.after(staff.unmount);
    assert.equal(staff.all('Form').length, 0);
    assert.equal(staff.all('img')[0].props.src, ticket.qr_url);
});

void test('cancelled tickets hide QR images and correction controls', (context) => {
    const ui = mount('resources/js/pages/event-registrations/Show.vue', {
        isOwner: true,
        financial: null,
        submissionKey: 'unused',
        registration: {
            ...registration,
            cancelled_at: '2026-10-14T01:00:00Z',
            tickets: [{ ...ticket, valid: false, qr_url: null }],
        },
    });
    context.after(ui.unmount);
    assert.equal(ui.all('img').length, 0);
    assert.equal(ui.all('Form').length, 0);
    assert.match(ui.text(), /These tickets are invalid/);
});

void test('checked in attendee names are locked while the registration can still be cancelled', (context) => {
    const ui = mount(
        'resources/js/pages/event-registrations/Show.vue',
        {
            isOwner: true,
            financial: null,
            submissionKey: 'unused',
            registration: {
                ...registration,
                tickets: [{ ...ticket, checked_in_at: '2027-01-20T08:00:00Z' }],
            },
        },
        null,
        '/',
        { '@inertiajs/vue3': { Form } },
    );
    context.after(ui.unmount);
    assert.equal(ui.all('Form').length, 1);
    assert.equal(
        ui.all('Form')[0].props.action,
        '/event-registrations/3/cancel',
    );
    assert.match(ui.text(), /Checked in/);
});

void test('the individual ticket prints its QR and links back to the private registration', (context) => {
    const print = context.mock.fn();
    const originalWindow = globalThis.window;
    globalThis.window = { print };
    context.after(() => {
        if (originalWindow === undefined) delete globalThis.window;
        else globalThis.window = originalWindow;
    });
    const ui = mount('resources/js/pages/event-tickets/Show.vue', {
        ticket,
        event,
        registrationId: 3,
    });
    context.after(ui.unmount);
    assert.deepEqual(ui.layout, []);
    assert.equal(ui.all('img')[0].props.src, ticket.qr_url);
    assert.equal(ui.all('Link')[0].props.href.url, '/event-registrations/3');
    ui.all('Button')
        .find((node) => ui.text(node).trim() === 'Print ticket')
        .props.onClick();
    assert.equal(print.mock.callCount(), 1);
});

void test('paid registration shows its reservation instructions and payment form without premature tickets', (context) => {
    const ui = mount(
        'resources/js/pages/event-registrations/Show.vue',
        {
            isOwner: true,
            submissionKey: 'request-key',
            financial: { payment: null, cancellations: [] },
            registration: {
                ...registration,
                status: 'pending-payment',
                status_label: 'Awaiting payment',
                price_centavos: 12550,
                total_centavos: 25100,
                expires_at: '2026-10-15T00:00:00Z',
                payment_instructions: 'Pay at the office.',
                tickets: [
                    { ...ticket, valid: false, ticket_url: null, qr_url: null },
                ],
            },
        },
        null,
        '/',
        { '@inertiajs/vue3': { Form } },
    );
    context.after(ui.unmount);
    assert.match(ui.text(), /251\.00/);
    assert.match(ui.text(), /Pay at the office/);
    assert.match(ui.text(), /Ticket not issued yet/);
    assert.equal(ui.all('img').length, 0);
    assert.ok(
        ui
            .all('Form')
            .some(
                (node) =>
                    node.props.action === '/event-registrations/3/payment',
            ),
    );
    assert.ok(Object.hasOwn(ui.all('EventPaymentFields')[0].props, 'receipt'));
    assert.ok(!ui.all('Link').some((node) => node.props.href === null));
});

void test('submitted paid registrations request cancellation while expired ones hide all mutation controls', (context) => {
    const submitted = mount(
        'resources/js/pages/event-registrations/Show.vue',
        {
            isOwner: true,
            submissionKey: 'cancellation-key',
            financial: { payment: null, cancellations: [] },
            registration: {
                ...registration,
                status: 'awaiting-review',
                status_label: 'Awaiting review',
                price_centavos: 10000,
                total_centavos: 10000,
                tickets: [],
            },
        },
        null,
        '/',
        { '@inertiajs/vue3': { Form } },
    );
    context.after(submitted.unmount);
    assert.match(submitted.text(), /places remain reserved/i);
    assert.equal(submitted.all('Form').length, 1);
    assert.equal(
        submitted.all('Form')[0].props.action,
        '/event-registrations/3/cancellation-requests',
    );
    assert.equal(submitted.all('input')[0].props.value, 'cancellation-key');
    const expired = mount('resources/js/pages/event-registrations/Show.vue', {
        isOwner: true,
        financial: null,
        submissionKey: 'unused',
        registration: {
            ...registration,
            status: 'expired',
            status_label: 'Expired',
            price_centavos: 10000,
            total_centavos: 10000,
            tickets: [],
        },
    });
    context.after(expired.unmount);
    assert.equal(expired.all('Form').length, 0);
    assert.match(expired.text(), /places have been released/i);
});

void test('pending cancellation hides the repeat request while exposing history to the owner', (context) => {
    const ui = mount('resources/js/pages/event-registrations/Show.vue', {
        isOwner: true,
        submissionKey: 'unused',
        financial: {
            payment: null,
            cancellations: [
                { id: 4, status: 'pending', reason: 'Cannot attend' },
            ],
        },
        registration: {
            ...registration,
            status: 'awaiting-review',
            price_centavos: 10000,
            total_centavos: 10000,
            tickets: [],
        },
    });
    context.after(ui.unmount);
    assert.equal(ui.all('Form').length, 0);
    assert.equal(ui.all('EventFinancialHistory').length, 1);
});

const financial = {
    payment: {
        id: 6,
        status: 'submitted',
        amount_centavos: 10000,
        refunded_centavos: 0,
        refunds: [],
    },
    cancellations: [{ id: 4, status: 'pending', reason: 'Cannot attend' }],
};
void test('the finance queue exposes work counts and preserves search and Event filters through pagination', (context) => {
    const ui = mount('resources/js/pages/officer/event-finances/Index.vue', {
        registrations: [],
        canReviewPayments: true,
        canReviewRefunds: false,
        pagination: { current_page: 2, last_page: 3, total: 60 },
        workOptions: [
            { value: 'attention', label: 'Needs attention', count: 60 },
            { value: 'payment-review', label: 'Payment review', count: 60 },
            { value: 'all', label: 'All registrations', count: 80 },
        ],
        table: {
            searchables: ['event', 'account', 'reference', 'registration'],
            filters: ['event'],
            filterOptions: {
                event: [{ value: '7', label: 'Community afternoon' }],
            },
            values: { work: 'attention', search: 'Maria', event: '7' },
        },
    });
    context.after(ui.unmount);
    assert.match(ui.text(), /Needs attention.*60/);
    assert.match(ui.text(), /All registrations.*80/);
    assert.equal(
        ui.all('DataTable')[0].props.action,
        '/officer/event-finances?work=attention',
    );
    assert.deepEqual(ui.all('DataTable')[0].props.values, {
        work: 'attention',
        search: 'Maria',
        event: '7',
    });
    const next = ui.all('Link').find((node) => ui.text(node).includes('Next'));
    assert.match(next.props.href.url, /page=3/);
    assert.match(next.props.href.url, /search=Maria/);
    assert.match(next.props.href.url, /event=7/);
    assert.match(next.props.href.url, /work=attention/);
    assert.equal(ui.all('Form').length, 0);
});
void test('the staff Manage dialog separates payment and refund permissions and closes after a decision', async (context) => {
    for (const [payments, refunds] of [
        [true, false],
        [false, true],
        [true, true],
    ]) {
        const ui = mount(
            'resources/js/pages/officer/event-finances/EventFinanceRowActions.vue',
            {
                item: {
                    id: registration.id,
                    work: ['payment-review', 'cancellation-review'],
                    registration: {
                        ...registration,
                        status: 'awaiting-review',
                        price_centavos: 10000,
                        total_centavos: 10000,
                    },
                    financial,
                    submission_key: 'refund-key',
                },
                canReviewPayments: payments,
                canReviewRefunds: refunds,
            },
            null,
            '/',
            { '@inertiajs/vue3': { Form } },
        );
        context.after(ui.unmount);
        assert.equal(ui.all('Dialog').length, 1);
        ui.all('Dialog')[0].props['onUpdate:open'](true);
        await vue.nextTick();
        assert.equal(ui.all('Dialog')[0].props.open, true);
        assert.equal(
            ui
                .all('Form')
                .some(
                    (node) =>
                        node.props.action ===
                        '/officer/event-payments/6/review',
                ),
            payments,
        );
        assert.equal(
            ui
                .all('Form')
                .some(
                    (node) =>
                        node.props.action ===
                        '/officer/event-cancellations/4/resolve',
                ),
            refunds,
        );
        const decisions = ui
            .all('Button')
            .filter((node) => node.props.name === 'decision')
            .map((node) => node.props.value);
        assert.deepEqual(decisions, [
            ...(payments ? ['confirmed', 'rejected'] : []),
            ...(refunds ? ['approved', 'declined'] : []),
        ]);
        ui.all('Form')[0].props.onSuccess();
        await vue.nextTick();
        assert.equal(ui.all('Dialog')[0].props.open, false);
        assert.equal(ui.all('Form')[0].props.options.preserveScroll, true);
    }
});

void test('recording an external refund closes Manage through the shared refund form', async (context) => {
    const ui = mount(
        'resources/js/pages/officer/event-finances/EventFinanceRowActions.vue',
        {
            item: {
                id: 3,
                registration: {
                    ...registration,
                    status: 'cancelled',
                    cancelled_at: '2026-10-14T00:00:00Z',
                },
                financial: {
                    payment: {
                        ...financial.payment,
                        status: 'confirmed',
                        refund_due_centavos: 6000,
                        refunded_centavos: 4000,
                    },
                    cancellations: [],
                },
                work: ['refund-due'],
                submission_key: 'stable-refund-key',
            },
            canReviewPayments: false,
            canReviewRefunds: true,
        },
        null,
        '/',
        { '@inertiajs/vue3': { Form } },
        ['@/components/EventRefundForm.vue'],
    );
    context.after(ui.unmount);
    ui.all('Dialog')[0].props['onUpdate:open'](true);
    await vue.nextTick();
    assert.equal(ui.all('Dialog')[0].props.open, true);
    assert.match(ui.text(), /Refund still due.*60\.00/);
    assert.match(ui.text(), /Refunds recorded.*40\.00/);
    ui.all('Form')[0].props.onSuccess();
    await vue.nextTick();
    assert.equal(ui.all('Dialog')[0].props.open, false);
});

void test('refund controls appear only after a confirmed paid registration is cancelled and money remains refundable', (context) => {
    const payment = { ...financial.payment, status: 'confirmed' };
    for (const [cancelled, refunded, due, allowed] of [
        [false, 0, 0, false],
        [true, 0, 10000, true],
        [true, 10000, 0, false],
    ]) {
        const ui = mount(
            'resources/js/pages/officer/event-finances/EventFinanceRowActions.vue',
            {
                item: {
                    id: registration.id,
                    work: due > 0 ? ['refund-due'] : [],
                    registration: {
                        ...registration,
                        status: cancelled ? 'cancelled' : 'confirmed',
                        cancelled_at: cancelled ? '2026-10-14T00:00:00Z' : null,
                        price_centavos: 10000,
                        total_centavos: 10000,
                    },
                    financial: {
                        payment: {
                            ...payment,
                            refunded_centavos: refunded,
                            refund_due_centavos: due,
                        },
                        cancellations: [],
                    },
                    submission_key: 'refund-key',
                },
                canReviewPayments: false,
                canReviewRefunds: true,
            },
        );
        context.after(ui.unmount);
        assert.equal(ui.all('EventRefundForm').length, allowed ? 1 : 0);
        if (allowed)
            assert.equal(ui.all('EventRefundForm')[0].props.remaining, 10000);
    }
});

void test('refund amounts convert exact PHP decimals and keep a stable submission key', async (context) => {
    const ui = mount(
        'resources/js/components/EventRefundForm.vue',
        { paymentId: 6, remaining: 25100, submissionKey: 'refund-key' },
        null,
        '/',
        { '@inertiajs/vue3': { Form } },
    );
    context.after(ui.unmount);
    const input = ui
        .all('Input')
        .find((node) => node.props.id === 'refund-amount-6');
    input.props['onUpdate:modelValue']('125.50');
    await vue.nextTick();
    assert.equal(
        ui.all('input').find((node) => node.props.name === 'amount_centavos')
            .props.value,
        12550,
    );
    input.props['onUpdate:modelValue']('0.001');
    await vue.nextTick();
    assert.equal(
        ui.all('input').find((node) => node.props.name === 'amount_centavos')
            .props.value,
        '',
    );
    assert.equal(
        ui.all('input').find((node) => node.props.name === 'submission_key')
            .props.value,
        'refund-key',
    );
});

void test('financial history distinguishes cancellation approval from actual refunds and links private receipts', (context) => {
    const ui = mount('resources/js/components/EventFinancialHistory.vue', {
        financial: {
            payment: {
                ...financial.payment,
                status: 'confirmed',
                method: 'gcash',
                reference: 'GCash123',
                submitted_at: '2026-10-14T00:00:00Z',
                receipt_url: '/event-payments/6/receipt',
                reviewed_at: '2026-10-14T01:00:00Z',
                reviewed_by_user_id: 1,
                refunded_centavos: 5000,
                refunds: [
                    {
                        id: 9,
                        amount_centavos: 5000,
                        method: 'cash',
                        reference: 'Cash987',
                        reason: 'Partial refund',
                        refunded_at: '2026-10-14T02:00:00Z',
                        recorded_at: '2026-10-14T02:00:00Z',
                        recorded_by_user_id: 1,
                    },
                ],
            },
            cancellations: [
                {
                    id: 4,
                    status: 'approved',
                    reason: 'Cannot attend',
                    requested_at: '2026-10-14T00:00:00Z',
                    resolved_at: '2026-10-14T01:00:00Z',
                    resolved_by_user_id: 1,
                    resolution_reason: 'Approved',
                },
            ],
        },
    });
    context.after(ui.unmount);
    assert.match(ui.text(), /Money returned is recorded separately/);
    assert.match(ui.text(), /Partial refund/);
    assert.match(ui.text(), /50\.00/);
    assert.equal(ui.all('a')[0].props.href, '/event-payments/6/receipt');
});

void test('rescheduled paid registrations explain full refunds and keep cancellation available', (context) => {
    const ui = mount(
        'resources/js/pages/event-registrations/Show.vue',
        {
            registration: {
                ...registration,
                price_centavos: 10000,
                total_centavos: 10000,
                event: { ...event, status: 'rescheduled' },
            },
            isOwner: true,
            submissionKey: 'refund-request',
            financial: {
                payment: null,
                cancellations: [],
                full_refund_eligible: true,
            },
        },
        null,
        '/',
        { '@inertiajs/vue3': { Form } },
    );
    context.after(ui.unmount);
    assert.match(
        ui.text(),
        /whole-registration cancellation and a full refund/,
    );
    assert.ok(ui.all('EventScheduleNotice').length);
    assert.ok(
        ui
            .all('Form')
            .some(
                (node) =>
                    node.props.action ===
                    '/event-registrations/3/cancellation-requests',
            ),
    );
});

void test('refund history separates the amount owed from recorded money returned', (context) => {
    const ui = mount('resources/js/components/EventFinancialHistory.vue', {
        financial: {
            full_refund_eligible: false,
            cancellations: [],
            payment: {
                status: 'confirmed',
                amount_centavos: 10000,
                method: 'cash',
                reference: 'Receipt 1',
                submitted_at: '2026-10-16T00:00:00Z',
                refunded_centavos: 0,
                refund_due_centavos: 10000,
                refunds: [],
            },
        },
    });
    context.after(ui.unmount);
    assert.match(ui.text(), /Refund required:.*100\.00/);
    assert.match(ui.text(), /not been recorded as returned/);
    assert.doesNotMatch(ui.text(), /Refunds recorded:/);
});
