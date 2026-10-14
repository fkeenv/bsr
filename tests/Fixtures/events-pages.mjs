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
