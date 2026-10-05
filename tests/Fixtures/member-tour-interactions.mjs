import assert from 'node:assert/strict';
import { test } from 'node:test';
import { http, HttpResponseError } from '@inertiajs/core';
import { useHttp } from '@inertiajs/vue3';
import * as vue from 'vue';
import { mount } from './mount-vue.mjs';

const flush = async () => {
    await new Promise((resolve) => setImmediate(resolve));
    await vue.nextTick();
};

async function dashboard(context, acknowledged = false) {
    const requests = [];
    const drivers = [];
    const listeners = new Map();
    const previousClient = http.getClient();
    const previousDocument = globalThis.document;
    globalThis.document = { querySelector: () => ({}) };
    http.setClient({
        request: (config) =>
            new Promise((resolve, reject) => {
                requests.push({ config, resolve, reject });
                config.signal.addEventListener('abort', () =>
                    reject(
                        new DOMException('Request cancelled.', 'AbortError'),
                    ),
                );
            }),
    });
    const primitive = (name) =>
        vue.defineComponent({
            inheritAttrs: false,
            setup(_, { attrs, slots }) {
                return () => vue.h(name, attrs, slots.default?.());
            },
        });
    const open = vue.ref(true);
    const ui = mount(
        'resources/js/pages/Dashboard.vue',
        {
            memberships: [
                {
                    id: 1,
                    property_id: 1,
                    role: 'owner',
                    property_label: 'Block 1 Lot 1',
                },
            ],
            onboarding: {
                experience: 'member',
                version: 1,
                tour_acknowledged: acknowledged,
                steps: [
                    'announcements',
                    'statement-of-account',
                    'property-profile',
                ],
                completed_steps: ['announcements'],
            },
        },
        null,
        '/dashboard',
        {
            '@inertiajs/vue3': {
                useHttp,
                usePage: () => ({ props: { auth: { capabilities: {} } } }),
                Head: primitive('Head'),
                Link: primitive('Link'),
                router: {
                    on: (event, handler) => {
                        listeners.set(event, handler);
                        return () => listeners.delete(event);
                    },
                },
            },
            '@/components/ui/sidebar': {
                useSidebar: () => ({
                    isMobile: vue.ref(true),
                    open,
                    setOpen: (value) => {
                        open.value = value;
                    },
                }),
            },
            'driver.js': {
                driver: (config) => {
                    const instance = {
                        config,
                        active: false,
                        drive() {
                            this.active = true;
                        },
                        moveTo() {},
                        destroy() {
                            if (this.active) {
                                this.active = false;
                                config.onDestroyed();
                            }
                        },
                    };
                    drivers.push(instance);
                    return instance;
                },
            },
        },
    );
    context.after(() => {
        ui.unmount();
        http.setClient(previousClient);
        if (previousDocument === undefined) delete globalThis.document;
        else globalThis.document = previousDocument;
    });
    await flush();
    return {
        ...ui,
        requests,
        drivers,
        listeners,
        help: () =>
            ui
                .all('Button')
                .find((button) => button.props['data-tour'] === 'member-help')
                .props.onClick(),
        success: (index = 0) =>
            requests[index].resolve({ status: 204, data: '', headers: {} }),
    };
}

void test('Help after saved acknowledgement does not submit or reset the checklist', async (context) => {
    const ui = await dashboard(context, true);
    const checklist = ui.all('OnboardingChecklist')[0].props.items;
    assert.equal(ui.drivers.length, 0);

    ui.help();
    await flush();
    ui.drivers[0].config.onDestroyStarted();
    await flush();

    assert.equal(ui.requests.length, 0);
    assert.deepEqual(ui.all('OnboardingChecklist')[0].props.items, checklist);
});

void test('Help while acknowledgement is saving does not submit again', async (context) => {
    const ui = await dashboard(context);
    ui.drivers[0].config.onDestroyStarted();
    await flush();

    ui.help();
    await flush();
    ui.drivers[1].config.onDestroyStarted();
    await flush();

    assert.equal(ui.requests.length, 1);
    ui.success();
    await flush();
    assert.doesNotMatch(ui.text(), /Saving your tour preference|Retry/);
});

void test('navigation interrupts the tour without saving a dismissal', async (context) => {
    const ui = await dashboard(context);

    ui.listeners.get('start')();
    await flush();

    assert.equal(ui.drivers[0].active, false);
    assert.equal(ui.requests.length, 0);
});

void test('unmount interrupts the tour without saving a dismissal', async (context) => {
    const ui = await dashboard(context);

    ui.unmount();
    await flush();

    assert.equal(ui.drivers[0].active, false);
    assert.equal(ui.requests.length, 0);
});

void test('unmount cancels a pending acknowledgement without an unhandled rejection', async (context) => {
    const ui = await dashboard(context);
    ui.drivers[0].config.onDestroyStarted();
    await flush();

    ui.unmount();
    await flush();

    assert.equal(ui.requests[0].config.signal.aborted, true);
    assert.equal(ui.requests.length, 1);
});

void test('Done closes immediately while acknowledgement awaits success without duplicate requests', async (context) => {
    const ui = await dashboard(context);

    ui.drivers[0].config.steps.at(-1).popover.onNextClick();
    await flush();

    assert.equal(ui.drivers[0].active, false);
    assert.equal(ui.requests.length, 1);
    assert.equal(
        ui.requests[0].config.url,
        '/onboarding/member/tour-acknowledgement',
    );
    assert.equal(ui.requests[0].config.method, 'post');
    assert.match(ui.text(), /Saving your tour preference/);
    ui.drivers[0].config.onDestroyStarted();
    assert.equal(ui.requests.length, 1);
    ui.success();
    await flush();
    assert.doesNotMatch(ui.text(), /Saving your tour preference|Retry/);
});

for (const failure of ['HTTP', 'network', 'validation']) {
    void test(`${failure} failure offers a retry that saves without replaying or resetting progress`, async (context) => {
        const ui = await dashboard(context);
        ui.drivers[0].config.onDestroyStarted();
        await flush();
        if (failure === 'network') {
            ui.requests[0].reject(new Error('Connection lost.'));
        } else {
            ui.requests[0].reject(
                new HttpResponseError(
                    'Save failed.',
                    {
                        status: failure === 'validation' ? 422 : 503,
                        data: JSON.stringify({
                            errors: { version: 'This tour has changed.' },
                        }),
                        headers: {},
                    },
                    ui.requests[0].config.url,
                ),
            );
        }
        await flush();

        assert.equal(ui.drivers[0].active, false);
        assert.match(ui.text(), /couldn’t save your tour preference/);
        const checklist = ui.all('OnboardingChecklist')[0].props.items;
        assert.equal(checklist[0].isComplete, true);
        assert.equal(checklist[1].isComplete, false);
        const retry = ui
            .all('Button')
            .find((button) =>
                button.children.some((child) => child.text?.includes('Retry')),
            );
        assert.ok(retry);
        retry.props.onClick();
        retry.props.onClick();
        await flush();
        assert.equal(ui.requests.length, 2);
        assert.equal(ui.drivers.length, 1);
        assert.match(ui.text(), /Saving your tour preference/);
        ui.success(1);
        await flush();
        assert.doesNotMatch(
            ui.text(),
            /couldn’t save|Retry|Saving your tour preference/,
        );
        assert.deepEqual(
            ui.all('OnboardingChecklist')[0].props.items,
            checklist,
        );
        ui.help();
        await flush();
        ui.drivers[1].config.onDestroyStarted();
        await flush();
        assert.equal(ui.requests.length, 2);
    });
}
