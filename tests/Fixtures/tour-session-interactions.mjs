import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { mount } from './mount-vue.mjs';

const flush = async () => {
    await new Promise((resolve) => setImmediate(resolve));
    await vue.nextTick();
};

function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((success, failure) => {
        resolve = success;
        reject = failure;
    });
    return { promise, resolve, reject };
}

function harness(context, steps) {
    const drivers = [];
    const listeners = new Set();
    const counts = { ends: 0, dismissals: 0 };
    const previousDocument = globalThis.document;
    globalThis.document = { querySelector: () => ({}) };
    const open = vue.ref(false);
    const ui = mount(
        'tests/Fixtures/TourHarness.vue',
        {
            steps,
            onEnd: () => {
                counts.ends++;
            },
            onDismiss: () => {
                counts.dismissals++;
            },
        },
        null,
        '/dashboard',
        {
            '@inertiajs/vue3': {
                router: {
                    on: (_, handler) => {
                        listeners.add(handler);
                        return () => listeners.delete(handler);
                    },
                },
            },
            '@/components/ui/sidebar': {
                useSidebar: () => ({
                    isMobile: vue.ref(false),
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
                        drives: 0,
                        moves: [],
                        destroys: 0,
                        drive() {
                            this.active = true;
                            this.drives++;
                        },
                        moveTo(index) {
                            this.moves.push(index);
                        },
                        destroy() {
                            this.destroys++;
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
        if (previousDocument === undefined) delete globalThis.document;
        else globalThis.document = previousDocument;
    });
    return {
        ...ui,
        drivers,
        listeners,
        counts,
        click: (action) =>
            ui
                .all('button')
                .find((button) => button.props['data-action'] === action)
                .props.onClick(),
        state: (name) =>
            ui.all('span').find((span) => span.props['data-state'] === name)
                .children[0].text,
    };
}

void test('cancelling initial preparation resolves safely without starting an overlay', async (context) => {
    const preparation = deferred();
    let signal;
    const ui = harness(context, [
        {
            target: 'initial',
            title: 'Initial',
            description: '',
            prepare: (value) => {
                signal = value;
                return preparation.promise;
            },
        },
    ]);
    const started = ui.click('start');
    await flush();

    ui.click('cancel');
    await started;
    preparation.resolve();
    await flush();

    assert.equal(ui.drivers[0].drives, 0);
    assert.equal(ui.listeners.size, 0);
    assert.equal(ui.state('running'), 'false');
    assert.equal(ui.counts.ends, 1);
    assert.equal(ui.counts.dismissals, 0);
    assert.equal(signal.aborted, true);
});

void test('cancellation as preparation settles cannot reactivate the cancelled Driver', async (context) => {
    for (let delay = 0; delay < 8; delay++) {
        await context.test(
            `completion interleaving ${delay}`,
            async (scenario) => {
                const preparation = deferred();
                const ui = harness(scenario, [
                    {
                        target: 'initial',
                        title: 'Initial',
                        description: '',
                        prepare: () => preparation.promise,
                    },
                ]);
                const started = ui.click('start');
                await flush();
                let drivesAtCancellation;
                const cancel = (remaining) => {
                    if (remaining > 0) {
                        queueMicrotask(() => cancel(remaining - 1));
                    } else {
                        drivesAtCancellation = ui.drivers[0].drives;
                        ui.click('cancel');
                    }
                };

                preparation.resolve();
                cancel(delay);
                await started;
                await flush();

                assert.equal(ui.drivers[0].drives, drivesAtCancellation);
                assert.equal(ui.drivers[0].active, false);
                assert.equal(ui.listeners.size, 0);
                assert.equal(ui.state('running'), 'false');
            },
        );
    }
});

void test('preparation serializes rapid Next and Back transitions', async (context) => {
    const preparation = deferred();
    let prepares = 0;
    const ui = harness(context, [
        { target: 'initial', title: 'Initial', description: '' },
        {
            target: 'next',
            title: 'Next',
            description: '',
            prepare: () => {
                prepares++;
                return preparation.promise;
            },
        },
    ]);
    await ui.click('start');

    ui.drivers[0].config.steps[0].popover.onNextClick();
    ui.drivers[0].config.steps[0].popover.onNextClick();
    await flush();
    assert.equal(prepares, 1);
    assert.deepEqual(ui.drivers[0].moves, []);
    preparation.resolve();
    await flush();
    ui.drivers[0].config.steps[1].popover.onPrevClick();
    await flush();

    assert.deepEqual(ui.drivers[0].moves, [1, 0]);
    assert.equal(ui.drivers[0].config.allowKeyboardControl, true);
});

void test('reduced motion disables Driver animations without skipping preparation', async (context) => {
    const previousWindow = globalThis.window;
    globalThis.window = { matchMedia: () => ({ matches: true }) };
    context.after(() => {
        if (previousWindow === undefined) delete globalThis.window;
        else globalThis.window = previousWindow;
    });
    const preparation = deferred();
    const ui = harness(context, [
        {
            target: 'initial',
            title: 'Initial',
            description: '',
            prepare: () => preparation.promise,
        },
    ]);
    const started = ui.click('start');
    await flush();

    assert.equal(ui.drivers[0].config.animate, false);
    assert.equal(ui.drivers[0].config.smoothScroll, false);
    assert.equal(ui.drivers[0].drives, 0);
    preparation.resolve();
    await started;
    assert.equal(ui.drivers[0].drives, 1);
});

void test('closing restores the temporary sidebar reveal once', async (context) => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = harness(context, [
        { target: 'navigation', title: 'administrator', description: '' },
    ]);
    const started = ui.click('start');
    await flush();

    assert.equal(ui.state('sidebar'), 'true');
    assert.equal(ui.state('section'), 'administrator');
    context.mock.timers.tick(250);
    await started;
    ui.drivers[0].config.onDestroyStarted();
    ui.drivers[0].config.onDestroyed();
    await flush();

    assert.equal(ui.state('sidebar'), 'false');
    assert.equal(ui.state('section'), 'membership');
    assert.equal(ui.counts.ends, 1);
    assert.equal(ui.counts.dismissals, 1);
});

void test('navigation owns its section after cancelling a pending sidebar reveal', async (context) => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = harness(context, [
        { target: 'navigation', title: 'administrator', description: '' },
    ]);
    const started = ui.click('start');
    await flush();

    for (const listener of ui.listeners) listener();
    ui.click('destination');
    await started;
    context.mock.timers.tick(250);
    await flush();

    assert.equal(ui.drivers[0].drives, 0);
    assert.equal(ui.state('section'), 'officer');
    assert.equal(ui.listeners.size, 0);
    assert.equal(ui.counts.ends, 1);
    assert.equal(ui.counts.dismissals, 0);
});

for (const interruption of ['navigation', 'unmount']) {
    void test(`${interruption} cancels waiting initial preparation`, async (context) => {
        const preparation = deferred();
        const ui = harness(context, [
            {
                target: 'initial',
                title: 'Initial',
                description: '',
                prepare: () => preparation.promise,
            },
        ]);
        const started = ui.click('start');
        await flush();

        if (interruption === 'navigation') {
            for (const listener of ui.listeners) listener();
        } else {
            ui.unmount();
        }
        await started;
        preparation.resolve();
        await flush();

        assert.equal(ui.drivers[0].drives, 0);
        assert.equal(ui.listeners.size, 0);
        assert.equal(ui.counts.ends, 1);
        assert.equal(ui.counts.dismissals, 0);
    });
}

void test('replacement cancels waiting initial preparation and ignores old callbacks', async (context) => {
    const preparation = deferred();
    const ui = harness(context, [
        {
            target: 'initial',
            title: 'Initial',
            description: '',
            prepare: () => preparation.promise,
        },
    ]);
    const first = ui.click('start');
    await flush();

    ui.values.steps = [
        { target: 'replacement', title: 'Replacement', description: '' },
    ];
    await vue.nextTick();
    await ui.click('start');
    await first;
    preparation.resolve();
    ui.drivers[0].config.onDestroyStarted();
    ui.drivers[0].config.onDestroyed();
    await flush();

    assert.equal(ui.drivers[0].drives, 0);
    assert.equal(ui.drivers[1].drives, 1);
    assert.equal(ui.drivers[1].active, true);
    assert.equal(ui.counts.ends, 1);
    assert.equal(ui.counts.dismissals, 0);
    assert.equal(ui.listeners.size, 1);
});

for (const phase of ['initial', 'transition']) {
    void test(`${phase} preparation rejection cleans up once and allows replay`, async (context) => {
        const failed = {
            target: 'failed',
            title: 'Failed',
            description: '',
            prepare: () => Promise.reject(new Error('Target unavailable.')),
        };
        const steps =
            phase === 'initial'
                ? [failed]
                : [
                      { target: 'initial', title: 'Initial', description: '' },
                      failed,
                  ];
        const ui = harness(context, steps);

        await ui.click('start');
        if (phase === 'transition')
            ui.drivers[0].config.steps[0].popover.onNextClick();
        await flush();

        assert.equal(ui.counts.ends, 1);
        assert.equal(ui.counts.dismissals, 0);
        assert.equal(ui.drivers[0].destroys, 1);
        assert.equal(ui.listeners.size, 0);
        assert.equal(ui.state('running'), 'false');
        ui.values.steps = [
            { target: 'replay', title: 'Replay', description: '' },
        ];
        await vue.nextTick();
        await ui.click('start');
        assert.equal(ui.drivers[1].active, true);
        assert.equal(ui.state('running'), 'true');
    });
}

void test('unfinished preparation cannot advance a replacement Driver', async (context) => {
    const preparation = deferred();
    const ui = harness(context, [
        { target: 'initial', title: 'Initial', description: '' },
        {
            target: 'next',
            title: 'Next',
            description: '',
            prepare: () => preparation.promise,
        },
    ]);
    await ui.click('start');
    ui.drivers[0].config.steps[0].popover.onNextClick();
    await flush();

    await ui.click('start');
    preparation.resolve();
    await flush();

    assert.deepEqual(ui.drivers[1].moves, []);
    assert.equal(ui.drivers[1].active, true);
    assert.equal(ui.counts.ends, 1);
    assert.equal(ui.counts.dismissals, 0);
    assert.equal(ui.listeners.size, 1);
});
