import assert from 'node:assert/strict';
import { test } from 'node:test';
import { mount } from './mount-vue.mjs';
import * as vue from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const editorModules = ['@/pages/officer/events/EventFormFields.vue'];
const formAdapter = {
    '@inertiajs/vue3': {
        useForm,
        Form: vue.defineComponent({
            inheritAttrs: false,
            setup:
                (_, { attrs, slots }) =>
                () =>
                    vue.h(
                        'Form',
                        attrs,
                        slots.default?.({ errors: {}, processing: false }),
                    ),
        }),
    },
};

function mountEventPage(file, props = {}) {
    let form;
    const ui = mount(
        file,
        props,
        null,
        '/',
        {
            '@inertiajs/vue3': {
                ...formAdapter['@inertiajs/vue3'],
                useForm: (values) => {
                    form = useForm(values);
                    return form;
                },
            },
        },
        [...editorModules, './EventSummary.vue'],
    );
    return { ...ui, form };
}

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

void test('Event authoring shows four visible sections and keeps payment instructions when switching to free', async (context) => {
    const ui = mount(
        'resources/js/pages/officer/events/Create.vue',
        {},
        null,
        '/',
        formAdapter,
        editorModules,
    );
    context.after(ui.unmount);
    for (const heading of [
        'Details',
        'Schedule',
        'Attendance',
        'Price & payment',
    ]) {
        assert.ok(
            ui.all('CardTitle').some((node) => ui.text(node).includes(heading)),
        );
    }
    const price = () =>
        ui.all('Input').find((node) => node.props.id === 'price');
    assert.equal(ui.all('Textarea').length, 0);
    price().props['onUpdate:modelValue']('125.50');
    await vue.nextTick();
    ui.all('Textarea')[0].props['onUpdate:modelValue']('Pay at the office.');
    price().props['onUpdate:modelValue']('0');
    await vue.nextTick();
    assert.equal(ui.all('Textarea').length, 0);
    price().props['onUpdate:modelValue']('125.50');
    await vue.nextTick();
    assert.equal(ui.all('Textarea')[0].props.modelValue, 'Pay at the office.');
    assert.equal(ui.all('Textarea')[0].props.required, true);
});

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
    const ui = mountEventPage('resources/js/pages/officer/events/Edit.vue', {
        event: { ...event, status: 'published' },
    });
    context.after(ui.unmount);
    const inputs = ui.all('Input');
    const start = inputs.find((node) => node.props.name === 'starts_at');
    assert.equal(start.props.modelValue, '2027-01-20T16:00');
    assert.equal(start.props.readonly, true);
    assert.notEqual(start.props.disabled, true);
    assert.equal(
        inputs.find((node) => node.props.name === 'registration_deadline').props
            .readonly,
        false,
    );
    assert.equal(ui.all('Select')[0].props.name, 'audience');
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
        { '@inertiajs/vue3': { Form, useForm } },
    );
    context.after(draft.unmount);
    assert.ok(
        draft
            .all('Form')
            .some((node) => node.props.action === '/officer/events/7/publish'),
    );
    assert.match(draft.text(), /Cancel this Event/);
    const cancelled = mountEventPage(
        'resources/js/pages/officer/events/Edit.vue',
        {
            event: { ...event, status: 'cancelled' },
        },
    );
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
    const requests = [];
    context.mock.method(router, 'visit', (url, options) =>
        requests.push({ url, ...options }),
    );
    const ui = mountEventPage('resources/js/pages/officer/events/Edit.vue', {
        event: { ...event, price_centavos: 12550 },
    });
    context.after(ui.unmount);
    const submit = () =>
        ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    submit();
    assert.equal(requests.at(-1).data.price_centavos, 12550);
    assert.match(requests.at(-1).url, /_method=PUT/);
    assert.equal(requests.at(-1).method, 'post');
    assert.equal(ui.all('Textarea')[0].props.required, true);
    ui.all('Input')
        .find((node) => node.props.id === 'price')
        .props['onUpdate:modelValue']('0');
    await vue.nextTick();
    submit();
    assert.equal(requests.at(-1).data.price_centavos, 0);
    assert.equal(ui.all('Textarea').length, 0);
    ui.form.price = '1.001';
    await vue.nextTick();
    submit();
    assert.equal(requests.at(-1).data.price_centavos, '');
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
        { '@inertiajs/vue3': { Form, useForm } },
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

void test('registered Event fields show their saved values and reserved minimum while other edits remain submittable', async (context) => {
    const requests = [];
    context.mock.method(router, 'visit', (url, options) =>
        requests.push({ url, ...options }),
    );
    const ui = mountEventPage('resources/js/pages/officer/events/Edit.vue', {
        event: { ...event, price_centavos: 12550 },
        management: { registration_fields_locked: true, reserved_places: 4 },
    });
    context.after(ui.unmount);
    assert.equal(ui.all('Select').length, 0);
    assert.notEqual(
        ui.all('Input').find((node) => node.props.id === 'audience').props
            .readonly,
        undefined,
    );
    assert.equal(
        ui.all('Input').find((node) => node.props.id === 'price').props
            .readonly,
        true,
    );
    assert.equal(
        ui.all('Input').find((node) => node.props.id === 'capacity').props.min,
        4,
    );
    assert.match(ui.text(), /4 places currently reserved/);
    assert.match(ui.text(), /including any cancelled registrations/);
    ui.form.title = 'Updated gathering';
    ui.form.capacity = '';
    ui.form.price = '0';
    ui.form.audience = 'members-only';
    await vue.nextTick();
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    assert.equal(requests[0].data.price_centavos, 12550);
    assert.equal(requests[0].data.audience, 'public');
    assert.equal(requests[0].data.capacity, '');
    assert.equal(requests[0].data.title, 'Updated gathering');
});

void test('unsaved Event edits guard publication until a successful save and cancellation stays available', async (context) => {
    const requests = [];
    context.mock.method(router, 'visit', (url, options) =>
        requests.push({ url, ...options }),
    );
    const ui = mountEventPage('resources/js/pages/officer/events/Edit.vue', {
        event,
    });
    context.after(ui.unmount);
    const publishButton = () =>
        ui
            .all('Button')
            .find((node) => ui.text(node).includes('Publish Event'));
    assert.equal(publishButton().props.disabled, false);
    ui.form.description = '<p>A changed description.</p>';
    await vue.nextTick();
    assert.equal(publishButton().props.disabled, true);
    assert.match(
        ui.text(),
        /Save your changes before publishing or rescheduling/,
    );
    assert.match(ui.text(), /Unsaved edits will be discarded/);
    assert.ok(
        ui
            .all('Button')
            .some(
                (node) =>
                    ui.text(node).includes('Cancel Event') &&
                    !node.props.disabled,
            ),
    );
    const focus = context.mock.fn();
    ui.all('form')[0].querySelector = () => ({
        querySelector: () => ({ focus }),
    });
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    requests.at(-1).onStart();
    await vue.nextTick();
    assert.equal(ui.all('RichTextEditor')[0].props.disabled, true);
    requests.at(-1).onError({ title: 'Provide a title.' });
    requests.at(-1).onFinish();
    await vue.nextTick();
    await vue.nextTick();
    assert.equal(focus.mock.callCount(), 1);
    assert.equal(ui.all('RichTextEditor')[0].props.disabled, false);
    assert.equal(ui.form.description, '<p>A changed description.</p>');
    assert.equal(publishButton().props.disabled, true);
    assert.equal(
        ui
            .all('InputError')
            .filter((node) => node.props.message === 'Provide a title.').length,
        1,
    );
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    await requests.at(-1).onSuccess({
        props: {
            event: {
                ...event,
                description: '<p>A changed description.</p>',
            },
        },
    });
    await vue.nextTick();
    assert.equal(publishButton().props.disabled, false);
    const file = new File(['image'], 'event.jpg', { type: 'image/jpeg' });
    ui.all('Input')
        .find((node) => node.props.id === 'image')
        .props.onChange({ target: { files: [file] } });
    await vue.nextTick();
    assert.equal(publishButton().props.disabled, true);
    assert.equal(ui.form.image, file);
});

void test('cancelled Event management retains all saved sections without editable fields', (context) => {
    const ui = mountEventPage('resources/js/pages/officer/events/Edit.vue', {
        event: {
            ...event,
            status: 'cancelled',
            price_centavos: 12550,
            payment_instructions: 'Pay at the office.',
        },
    });
    context.after(ui.unmount);
    assert.match(ui.text(), /Cancelled/);
    assert.match(ui.text(), /Details/);
    assert.match(ui.text(), /Schedule/);
    assert.match(ui.text(), /Attendance/);
    assert.match(ui.text(), /Price & payment/);
    assert.match(ui.text(), /Clubhouse/);
    assert.match(ui.text(), /125\.50/);
    assert.match(ui.text(), /Pay at the office/);
    assert.equal(ui.all('RichTextContent')[0].props.html, event.description);
    assert.equal(ui.all('Input').length, 0);
    assert.equal(ui.all('form').length, 0);
    assert.equal(ui.all('Form').length, 0);
});

void test('rescheduling requires saved details and refreshes the read-only schedule after success', async (context) => {
    const ui = mountEventPage('resources/js/pages/officer/events/Edit.vue', {
        event: { ...event, status: 'published' },
    });
    context.after(ui.unmount);
    const reschedule = () =>
        ui.all('Button').find((node) => ui.text(node) === ' Reschedule Event');
    assert.equal(reschedule().props.disabled, false);
    ui.form.title = 'An unsaved title';
    await vue.nextTick();
    assert.equal(reschedule().props.disabled, true);
    const request = ui
        .all('Form')
        .find((node) => node.props.action === '/officer/events/7/reschedule');
    assert.equal(request.props.onBefore(), false);
    ui.form.reset();
    await vue.nextTick();
    assert.equal(request.props.onBefore(), true);
    request.props.onStart();
    await vue.nextTick();
    assert.equal(ui.all('fieldset')[0].props.disabled, true);
    ui.values.event = {
        ...event,
        status: 'rescheduled',
        starts_at: '2027-02-01T08:00:00Z',
        ends_at: '2027-02-01T10:00:00Z',
        registration_deadline: '2027-01-31T04:00:00Z',
    };
    await request.props.onSuccess();
    request.props.onFinish();
    await vue.nextTick();
    assert.equal(
        ui.all('Input').find((node) => node.props.id === 'starts_at').props
            .modelValue,
        '2027-02-01T16:00',
    );
    assert.equal(ui.form.isDirty, false);
    assert.equal(reschedule().props.disabled, false);
});
