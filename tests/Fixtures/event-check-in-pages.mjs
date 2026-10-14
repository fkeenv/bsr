import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { mount } from './mount-vue.mjs';
const event = {
    id: 7,
    title: 'Community afternoon',
    starts_at: '2027-01-20T08:00:00Z',
    venue: 'Clubhouse',
    audience_label: 'Public',
};
const attendee = {
    id: 4,
    name: 'Maria Santos',
    account_name: 'Jose Santos',
    status_label: 'Confirmed',
    valid: true,
    ticket: 'opaque-ticket',
    checked_in_at: null,
    checked_in_by_user_id: null,
};
const props = {
    event,
    search: '',
    attendees: { data: [attendee], total: 1, current_page: 1, last_page: 1 },
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
function httpAdapter(handler) {
    const calls = [];
    const state = vue.reactive({
        ticket: '',
        processing: false,
        errors: {},
        clearErrors() {
            this.errors = {};
        },
        cancel() {},
        async submit(route) {
            calls.push({ route, ticket: this.ticket });
            return handler(this);
        },
    });
    return {
        calls,
        state,
        adapters: { '@inertiajs/vue3': { Form, useHttp: () => state } },
    };
}
function cameraEnvironment(
    context,
    {
        supported = true,
        qr = true,
        acquire,
        detect = async () => [],
        secure = true,
    } = {},
) {
    const stop = context.mock.fn();
    const stream = { getTracks: () => [{ stop }] };
    const getUserMedia = context.mock.fn(acquire ?? (async () => stream));
    class Detector {
        static async getSupportedFormats() {
            return qr ? ['qr_code'] : ['ean_13'];
        }
        detect = detect;
    }
    for (const [key, value] of Object.entries({
        isSecureContext: secure,
        navigator: { mediaDevices: { getUserMedia } },
        BarcodeDetector: supported ? Detector : undefined,
    })) {
        const descriptor = Object.getOwnPropertyDescriptor(globalThis, key);
        Object.defineProperty(globalThis, key, {
            configurable: true,
            value,
            writable: true,
        });
        context.after(() => {
            if (descriptor) Object.defineProperty(globalThis, key, descriptor);
            else delete globalThis[key];
        });
    }
    return { stop, stream, getUserMedia };
}
function cameraPage(context, onScan = () => {}) {
    const ui = mount('resources/js/components/EventQrScanner.vue', { onScan });
    context.after(ui.unmount);
    const preview = ui.all('video')[0];
    preview.play = async () => {};
    preview.readyState = 4;
    return ui;
}

void test('the event picker links to selected Event check-in and explains its empty state', (context) => {
    const ui = mount('resources/js/pages/officer/event-check-in/Index.vue', {
        events: [event],
    });
    context.after(ui.unmount);
    assert.equal(ui.all('Link')[0].props.href.url, '/officer/event-check-in/7');
    const empty = mount('resources/js/pages/officer/event-check-in/Index.vue', {
        events: [],
    });
    context.after(empty.unmount);
    assert.match(empty.text(), /no published Events/);
});

void test('manual admission sends the opaque ticket and presents a visible success and recorded time', async (context) => {
    const response = {
        outcome: 'checked-in',
        name: 'Maria Santos',
        ticket: 'opaque-ticket',
        checked_in_at: '2026-10-14T00:00:00Z',
        checked_in_by_user_id: 8,
    };
    const http = httpAdapter(async () => response);
    const ui = mount(
        'resources/js/pages/officer/event-check-in/Show.vue',
        props,
        null,
        '/',
        http.adapters,
        ['@/components/EventQrScanner.vue'],
    );
    context.after(ui.unmount);
    await ui
        .all('Button')
        .find((node) => ui.text(node).trim() === 'Check in attendee')
        .props.onClick();
    await vue.nextTick();
    assert.equal(http.calls[0].ticket, 'opaque-ticket');
    assert.equal(http.calls[0].route.url, '/officer/event-check-in/7');
    assert.match(ui.text(), /Checked in successfully/);
    assert.match(ui.text(), /8:00/);
    assert.match(ui.text(), /Staff account #8/);
    assert.equal(
        ui
            .all('Button')
            .find((node) => ui.text(node).trim() === 'Already checked in').props
            .disabled,
        true,
    );
});

void test('repeat scan results show already checked in without implying a second admission', async (context) => {
    const http = httpAdapter(async () => ({
        outcome: 'already-checked-in',
        name: 'Maria Santos',
        ticket: 'opaque-ticket',
        checked_in_at: '2026-10-14T00:00:00Z',
        checked_in_by_user_id: 8,
    }));
    const ui = mount(
        'resources/js/pages/officer/event-check-in/Show.vue',
        props,
        null,
        '/',
        http.adapters,
        ['@/components/EventQrScanner.vue'],
    );
    context.after(ui.unmount);
    await ui
        .all('Button')
        .find((node) => ui.text(node).trim() === 'Check in attendee')
        .props.onClick();
    await vue.nextTick();
    assert.match(ui.text(), /Already checked in/);
    assert.match(ui.text(), /No second admission was recorded/);
});

void test('invalid tickets remain disabled and failed requests show server errors without marking admission', async (context) => {
    const http = httpAdapter(async (state) => {
        state.errors = { ticket: 'This ticket belongs to a different Event.' };
        throw new Error('422');
    });
    const ui = mount(
        'resources/js/pages/officer/event-check-in/Show.vue',
        props,
        null,
        '/',
        http.adapters,
        ['@/components/EventQrScanner.vue'],
    );
    context.after(ui.unmount);
    await ui
        .all('Button')
        .find((node) => ui.text(node).trim() === 'Check in attendee')
        .props.onClick();
    await vue.nextTick();
    assert.match(ui.text(), /Admission not confirmed/);
    assert.match(ui.text(), /different Event/);
    assert.equal(
        ui
            .all('Button')
            .find((node) => ui.text(node).trim() === 'Check in attendee').props
            .disabled,
        false,
    );
    const invalid = mount(
        'resources/js/pages/officer/event-check-in/Show.vue',
        {
            ...props,
            attendees: {
                ...props.attendees,
                data: [
                    {
                        ...attendee,
                        ticket: null,
                        valid: false,
                        status_label: 'Awaiting payment',
                    },
                ],
            },
        },
        null,
        '/',
        http.adapters,
    );
    context.after(invalid.unmount);
    assert.equal(
        invalid
            .all('Button')
            .find((node) => ui.text(node).trim() === 'Check in attendee').props
            .disabled,
        true,
    );
});

void test('manual search stays usable when the camera is denied', async (context) => {
    const environment = cameraEnvironment(context, {
        acquire: async () => {
            throw new Error('NotAllowedError');
        },
    });
    const http = httpAdapter(async () => ({
        outcome: 'checked-in',
        name: attendee.name,
        ticket: attendee.ticket,
        checked_in_at: '2026-10-14T00:00:00Z',
        checked_in_by_user_id: 8,
    }));
    const ui = mount(
        'resources/js/pages/officer/event-check-in/Show.vue',
        props,
        null,
        '/',
        http.adapters,
        ['@/components/EventQrScanner.vue'],
    );
    context.after(ui.unmount);
    await ui
        .all('Button')
        .find((node) => ui.text(node).trim() === 'Start camera')
        .props.onClick();
    await vue.nextTick();
    assert.equal(environment.getUserMedia.mock.callCount(), 1);
    assert.match(ui.text(), /Camera access was denied/);
    assert.ok(ui.all('Input').some((node) => node.props.name === 'search'));
    await ui
        .all('Button')
        .find((node) => ui.text(node).trim() === 'Check in attendee')
        .props.onClick();
    await vue.nextTick();
    assert.match(ui.text(), /Checked in successfully/);
});

void test('unsupported or insecure camera environments explain the manual fallback without requesting camera access', async (context) => {
    for (const options of [
        { supported: false },
        { qr: false },
        { secure: false },
    ]) {
        const environment = cameraEnvironment(context, options);
        const ui = cameraPage(context);
        await ui.all('Button')[0].props.onClick();
        await vue.nextTick();
        assert.match(ui.text(), /Use attendee search below/);
        assert.equal(environment.getUserMedia.mock.callCount(), 0);
        ui.unmount();
    }
});

void test('successful QR scanning releases the camera and sends one value for server validation', async (context) => {
    let ui;
    const detector = context.mock.fn(async () => [
        { rawValue: 'https://localhost/event-tickets/opaque' },
    ]);
    const environment = cameraEnvironment(context, { detect: detector });
    const http = httpAdapter(async () => ({
        outcome: 'checked-in',
        name: attendee.name,
        ticket: attendee.ticket,
        checked_in_at: '2026-10-14T00:00:00Z',
        checked_in_by_user_id: 8,
    }));
    ui = mount(
        'resources/js/pages/officer/event-check-in/Show.vue',
        props,
        null,
        '/',
        http.adapters,
        ['@/components/EventQrScanner.vue'],
    );
    context.after(ui.unmount);
    ui.all('video')[0].play = async () => {};
    ui.all('video')[0].readyState = 4;
    await ui
        .all('Button')
        .find((node) => ui.text(node).trim() === 'Start camera')
        .props.onClick();
    await vue.nextTick();
    await new Promise(setImmediate);
    await vue.nextTick();
    assert.equal(detector.mock.callCount(), 1);
    assert.equal(environment.stop.mock.callCount(), 1);
    assert.equal(http.calls.length, 1);
    assert.equal(
        http.calls[0].ticket,
        'https://localhost/event-tickets/opaque',
    );
    assert.match(ui.text(), /Checked in successfully/);
});

void test('cancelling while camera permission is pending stops a late stream and never starts scanning', async (context) => {
    let grant;
    const pending = new Promise((resolve) => {
        grant = resolve;
    });
    const environment = cameraEnvironment(context, { acquire: () => pending });
    const ui = cameraPage(context);
    const starting = ui.all('Button')[0].props.onClick();
    await vue.nextTick();
    await Promise.resolve();
    ui.all('Button')
        .find((node) => ui.text(node).trim() === 'Cancel camera request')
        .props.onClick();
    grant(environment.stream);
    await starting;
    await vue.nextTick();
    assert.equal(environment.stop.mock.callCount(), 1);
    assert.match(ui.text(), /Start camera/);
});

void test('leaving the scanner releases the active camera and no pending detection can admit an attendee', async (context) => {
    let finish;
    const detector = context.mock.fn(
        () =>
            new Promise((resolve) => {
                finish = resolve;
            }),
    );
    const environment = cameraEnvironment(context, { detect: detector });
    const onScan = context.mock.fn();
    const ui = cameraPage(context, onScan);
    await ui.all('Button')[0].props.onClick();
    ui.unmount();
    finish([{ rawValue: 'stale-frame' }]);
    await Promise.resolve();
    assert.equal(environment.stop.mock.callCount(), 1);
    assert.equal(onScan.mock.callCount(), 0);
});
