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

async function dashboard(context, onboarding = null, isMobile = true) {
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
    const open = vue.ref(false);
    const ui = mount(
        'resources/js/pages/officer/Dashboard.vue',
        {
            onboarding,
        },
        null,
        '/officer',
        {
            '@inertiajs/vue3': {
                useHttp,
                usePage: () => ({
                    props: {
                        auth: {
                            user: { name: 'Ana Cruz' },
                            capabilities: { canAccessOfficer: true },
                        },
                    },
                }),
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
                    isMobile: vue.ref(isMobile),
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
                        moves: [],
                        drive() {
                            this.active = true;
                        },
                        moveTo(index) {
                            this.moves.push(index);
                        },
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
        sidebarOpen: open,
        help: () =>
            ui
                .all('Button')
                .find((button) => button.props['data-tour'] === 'officer-help')
                .props.onClick(),
        success: (index = 0) =>
            requests[index].resolve({ status: 204, data: '', headers: {} }),
    };
}

void test('the workspace remains useful without orientation and links to existing Officer areas', async (context) => {
    const ui = await dashboard(context);
    assert.match(ui.text(), /Hello\s*, Ana Cruz/);
    assert.match(ui.text(), /Payments & collection/);
    assert.match(ui.text(), /Properties & people/);
    const links = ui
        .all('Link')
        .map((node) => [
            ui.text(node).trim(),
            node.props.href.url,
            node.props.href.method,
        ]);
    for (const [label, destination] of [
        ['New draft', '/officer/announcements/create'],
        ['View Announcements', '/officer/announcements'],
        ['Review Payments', '/officer/payments'],
        ['Open the Unpaid roster', '/officer/unpaid'],
        ['View Charges', '/officer/charges'],
        ['Browse Properties', '/officer/properties'],
        ['Issue a Property Invitation', '/officer/property-invitations'],
        ['Manage Memberships', '/officer/memberships'],
    ]) {
        assert.ok(
            links.some(
                ([text, href, method]) =>
                    text === label && href === destination && method === 'get',
            ),
            label,
        );
    }
    assert.equal(ui.all('OnboardingChecklist').length, 0);
    assert.equal(ui.requests.length, 0);
    assert.doesNotMatch(ui.text(), /₱|pending Payments|total collected/);
    ui.help();
    await flush();
    assert.ok(ui.drivers[0].active);
    assert.equal(
        ui.drivers[0].config.steps.some(
            (step) => step.element === '[data-tour="officer-checklist"]',
        ),
        false,
    );
    assert.equal(
        ui.drivers[0].config.steps.some(
            (step) => step.element === '[data-tour="nav-officer"]',
        ),
        false,
    );
});

const orientation = (acknowledged = true, complete = false) => {
    const steps = [
        'officer-invitations',
        'officer-properties',
        'officer-memberships',
        'officer-payments',
        'officer-charges',
        'officer-announcements',
    ];
    return {
        experience: 'officer',
        version: 1,
        tour_acknowledged: acknowledged,
        steps,
        completed_steps: complete ? [...steps] : ['officer-charges'],
    };
};

void test('orientation is secondary, preserves completion links, and opens for Help', async (context) => {
    const ui = await dashboard(context, orientation());
    assert.equal(ui.all('details')[0].props.open, false);
    const checklist = ui.all('OnboardingChecklist')[0].props.items;
    assert.equal(checklist.length, 6);
    for (const item of checklist) {
        assert.equal(item.link.href.url, '/onboarding/officer/steps');
        assert.equal(item.link.href.method, 'post');
        assert.deepEqual(item.link.data, { step: item.key });
        assert.equal(item.link.as, 'button');
        assert.equal(item.isComplete, item.key === 'officer-charges');
    }
    assert.equal(ui.drivers.length, 0);
    ui.help();
    await flush();
    assert.equal(ui.drivers[0].active, true);
    assert.equal(
        ui
            .all('Button')
            .find((node) => node.props['data-tour'] === 'officer-help').props
            .disabled,
        true,
    );
    const checklistIndex = ui.drivers[0].config.steps.findIndex(
        (step) => step.element === '[data-tour="officer-checklist"]',
    );
    ui.drivers[0].config.steps[checklistIndex - 1].popover.onNextClick();
    await flush();
    assert.equal(ui.all('details')[0].props.open, true);
    ui.drivers[0].config.onDestroyStarted();
    await flush();
    assert.equal(ui.requests.length, 0);
    assert.deepEqual(ui.all('OnboardingChecklist')[0].props.items, checklist);
    ui.all('details')[0].props.onToggle({ target: { open: false } });
    await flush();
    assert.equal(ui.all('details')[0].props.open, false);
    assert.match(ui.text(ui.all('summary')[0]), /Continue orientation/);
});

void test('completed orientation leaves everyday shortcuts available and replays without resetting progress', async (context) => {
    const ui = await dashboard(context, orientation(true, true));
    const items = ui.all('OnboardingChecklist')[0].props.items;
    assert.ok(items.every((item) => item.isComplete));
    assert.equal(ui.all('Link').length, 8);
    assert.equal(ui.all('details')[0].props.open, false);
    ui.help();
    await flush();
    ui.drivers[0].config.onDestroyStarted();
    await flush();
    assert.equal(ui.requests.length, 0);
    assert.deepEqual(ui.all('OnboardingChecklist')[0].props.items, items);
});

void test('failed tour acknowledgement offers Retry without replaying or changing completion', async (context) => {
    const ui = await dashboard(context, orientation(false));
    assert.equal(ui.drivers[0].active, true);
    const items = ui.all('OnboardingChecklist')[0].props.items;
    ui.drivers[0].config.onDestroyStarted();
    await flush();
    assert.equal(
        ui.requests[0].config.url,
        '/onboarding/officer/tour-acknowledgement',
    );
    assert.equal(ui.requests[0].config.method, 'post');
    assert.match(ui.text(), /Saving your tour preference/);
    ui.requests[0].reject(
        new HttpResponseError(
            'Save failed.',
            { status: 503, data: '', headers: {} },
            ui.requests[0].config.url,
        ),
    );
    await flush();
    assert.ok(ui.all('div').some((node) => node.props.role === 'alert'));
    const retry = ui
        .all('Button')
        .find((node) => ui.text(node).trim() === 'Retry');
    assert.ok(retry);
    retry.props.onClick();
    retry.props.onClick();
    await flush();
    assert.equal(ui.requests.length, 2);
    assert.equal(ui.drivers.length, 1);
    ui.success(1);
    await flush();
    assert.doesNotMatch(
        ui.text(),
        /Retry|Saving your tour preference|couldn’t save/,
    );
    assert.deepEqual(ui.all('OnboardingChecklist')[0].props.items, items);
});

void test('desktop navigation cancels a waiting menu transition without recording tour dismissal', async (context) => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = await dashboard(context, orientation(false), false);
    ui.drivers[0].config.steps[0].popover.onNextClick();
    await flush();
    assert.equal(ui.all('details')[0].props.open, true);
    ui.drivers[0].config.steps[1].popover.onNextClick();
    await flush();
    assert.equal(ui.sidebarOpen.value, true);
    ui.listeners.get('start')();
    context.mock.timers.tick(250);
    await flush();
    assert.equal(ui.drivers[0].active, false);
    assert.equal(ui.sidebarOpen.value, false);
    assert.equal(ui.requests.length, 0);
    assert.equal(ui.listeners.size, 0);
    ui.help();
    await flush();
    assert.equal(ui.drivers[1].active, true);
});
