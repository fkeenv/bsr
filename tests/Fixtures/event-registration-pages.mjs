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
};
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
        { registration, isOwner: true },
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
    });
    context.after(staff.unmount);
    assert.equal(staff.all('Form').length, 0);
    assert.equal(staff.all('img')[0].props.src, ticket.qr_url);
});

void test('cancelled tickets hide QR images and correction controls', (context) => {
    const ui = mount('resources/js/pages/event-registrations/Show.vue', {
        isOwner: true,
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
