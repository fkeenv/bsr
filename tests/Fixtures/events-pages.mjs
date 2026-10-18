import assert from 'node:assert/strict';
import { test } from 'node:test';
import { mount } from './mount-vue.mjs';
import * as vue from 'vue';

const event = {
    id: 7,
    title: 'Community afternoon',
    description: '<p>Come together.</p>',
    excerpt: 'Come together.',
    image_url: '/events/7/image',
    venue: 'Clubhouse',
    starts_at: '2027-01-20T08:00:00Z',
    ends_at: '2027-01-20T10:00:00Z',
    registration_deadline: '2027-01-19T04:00:00Z',
    audience: 'public',
    audience_label: 'Public',
    status: 'draft',
    capacity: null,
    price_centavos: 0,
};

void test('Event images recover to a neutral card placeholder and omit failed detail imagery', async (context) => {
    const cards = mount('resources/js/pages/events/Index.vue', {
        events: [{ ...event, image_url: null }],
    });
    context.after(cards.unmount);
    assert.ok(
        cards
            .all('div')
            .some(
                (node) => node.props['data-slot'] === 'event-image-placeholder',
            ),
    );
    cards.values.events[0].image_url = '/events/7/image';
    await vue.nextTick();
    assert.equal(cards.all('img')[0].props.src, '/events/7/image');
    cards.all('img')[0].props.onError({
        currentTarget: { getAttribute: () => '/events/7/image' },
    });
    await vue.nextTick();
    assert.equal(cards.all('img').length, 0);
    assert.ok(
        cards
            .all('div')
            .some(
                (node) => node.props['data-slot'] === 'event-image-placeholder',
            ),
    );
    const details = mount('resources/js/pages/events/Show.vue', {
        event,
        registrationAvailability: 'Closed',
        submissionKey: null,
    });
    context.after(details.unmount);
    details.all('img')[0].props.onError({
        currentTarget: { getAttribute: () => '/events/7/image' },
    });
    await vue.nextTick();
    assert.equal(details.all('img').length, 0);
    assert.equal(
        details
            .all('div')
            .filter((node) => node.props['data-slot'] === 'content-image')
            .length,
        0,
    );
});

void test('Event discovery links to details and renders an empty state without leaking staff controls', (context) => {
    const ui = mount('resources/js/pages/events/Index.vue', {
        events: [event],
    });
    context.after(ui.unmount);
    assert.deepEqual(ui.layout, []);
    assert.ok(
        ui.all('Link').some((node) => node.props.href.url === '/events/7'),
    );
    assert.match(ui.text(), /January 20, 2027.*4:00/);
    assert.match(ui.text(), /Free/);
    assert.equal(ui.all('Form').length, 0);
    const empty = mount('resources/js/pages/events/Index.vue', { events: [] });
    context.after(empty.unmount);
    assert.match(empty.text(), /no Events available/);
});

void test('Event details display Manila dates, authorized image URLs, and sanitized body', (context) => {
    const ui = mount('resources/js/pages/events/Show.vue', {
        event,
        registrationAvailability: 'Sign in to register for this Event.',
        submissionKey: null,
    });
    context.after(ui.unmount);
    assert.deepEqual(ui.layout, []);
    assert.match(ui.text(), /4:00/);
    assert.match(ui.text(), /6:00/);
    assert.match(ui.text(), /Manila time/);
    assert.match(ui.text(), /No limit/);
    assert.equal(ui.all('RichTextContent')[0].props.html, event.description);
    assert.equal(ui.all('img')[0].props.src, '/events/7/image');
    assert.equal(ui.all('Link')[0].props.href.url, '/events');
});

void test('Event form uses Manila datetime inputs and preserves published times for submission', (context) => {
    const ui = mount('resources/js/pages/officer/events/EventFormFields.vue', {
        event: { ...event, status: 'published' },
        description: event.description,
        errors: { capacity: 'Too few places.' },
    });
    context.after(ui.unmount);
    const inputs = ui.all('Input');
    const start = inputs.find((node) => node.props.name === 'starts_at');
    assert.equal(start.props['default-value'], '2027-01-20T16:00');
    assert.equal(start.props.readonly, true);
    assert.notEqual(start.props.disabled, true);
    assert.equal(
        inputs.find((node) => node.props.name === 'registration_deadline').props
            .readonly,
        false,
    );
    assert.equal(ui.all('Select')[0].props.name, 'audience');
    assert.equal(
        ui
            .all('InputError')
            .find((node) => node.props.message === 'Too few places.').props
            .message,
        'Too few places.',
    );
});

void test('Event management shows publish and cancellation controls only before cancellation', (context) => {
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
    const draft = mount(
        'resources/js/pages/officer/events/Edit.vue',
        { event },
        null,
        '/',
        { '@inertiajs/vue3': { Form } },
    );
    context.after(draft.unmount);
    assert.ok(
        draft
            .all('Form')
            .some((node) => node.props.action === '/officer/events/7/publish'),
    );
    assert.match(draft.text(), /Cancel this Event/);
    const cancelled = mount('resources/js/pages/officer/events/Edit.vue', {
        event: { ...event, status: 'cancelled' },
    });
    context.after(cancelled.unmount);
    assert.equal(cancelled.all('Form').length, 0);
    assert.match(cancelled.text(), /history is retained/);
});

void test('paid Event discovery displays the fixed PHP ticket price', (context) => {
    const ui = mount('resources/js/pages/events/Index.vue', {
        events: [{ ...event, price_centavos: 12550 }],
    });
    context.after(ui.unmount);
    assert.match(ui.text(), /125\.50/);
    assert.doesNotMatch(ui.text(), /Free/);
});

void test('paid Event form converts PHP input to centavos without accepting sub-cent amounts', async (context) => {
    const ui = mount('resources/js/pages/officer/events/EventFormFields.vue', {
        event: { ...event, price_centavos: 12550 },
        description: event.description,
        errors: {},
    });
    context.after(ui.unmount);
    assert.equal(
        ui.all('input').find((node) => node.props.name === 'price_centavos')
            .props.value,
        12550,
    );
    assert.equal(ui.all('Textarea')[0].props.required, true);
    ui.all('Input')
        .find((node) => node.props.id === 'price')
        .props['onUpdate:modelValue']('0');
    await vue.nextTick();
    assert.equal(
        ui.all('input').find((node) => node.props.name === 'price_centavos')
            .props.value,
        0,
    );
    assert.equal(ui.all('Textarea')[0].props.required, false);
});

void test('rescheduled Events show a clear notice with the new schedule', (context) => {
    const ui = mount('resources/js/components/EventScheduleNotice.vue', {
        event: { ...event, status: 'rescheduled' },
    });
    context.after(ui.unmount);
    assert.match(ui.text(), /Rescheduled/);
    assert.match(ui.text(), /January 20, 2027/);
    assert.match(ui.text(), /original expiry/);
});

void test('published and rescheduled Event management offers a dedicated schedule form and retained history', (context) => {
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
    const ui = mount(
        'resources/js/pages/officer/events/Edit.vue',
        {
            event: { ...event, status: 'rescheduled' },
            scheduleChanges: [
                {
                    id: 1,
                    changed_by_user_id: 12,
                    changed_at: '2026-10-16T00:00:00Z',
                    old_starts_at: '2027-01-19T08:00:00Z',
                    old_ends_at: '2027-01-19T10:00:00Z',
                    old_registration_deadline: '2027-01-18T04:00:00Z',
                    new_starts_at: event.starts_at,
                    new_ends_at: event.ends_at,
                    new_registration_deadline: event.registration_deadline,
                },
            ],
        },
        null,
        '/',
        { '@inertiajs/vue3': { Form } },
    );
    context.after(ui.unmount);
    assert.match(ui.text(), /Reschedule Event/);
    assert.match(ui.text(), /Schedule history/);
    assert.match(ui.text(), /January 19, 2027/);
    assert.match(ui.text(), /Staff account #12/);
    const form = ui
        .all('Form')
        .find((node) => node.props.action === '/officer/events/7/reschedule');
    assert.ok(form);
    assert.equal(
        ui.all('Input').find((node) => node.props.name === 'starts_at').props[
            'default-value'
        ],
        '2027-01-20T16:00',
    );
});
