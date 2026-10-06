import assert from 'node:assert/strict';
import { test } from 'node:test';
import { Form, router } from '@inertiajs/vue3';
import * as vue from 'vue';
import { mount } from './mount-vue.mjs';

const property = {
    id: 7,
    block: '2',
    lot: '3',
    is_active: true,
    has_been_charged: false,
};
const flush = () => vue.nextTick();
const text = (node) =>
    [node.text ?? '', ...(node.children ?? []).map(text)].join(' ');

function setup(context, overrides = {}) {
    const requests = [];
    const previousFormData = globalThis.FormData;
    globalThis.FormData = class extends previousFormData {
        constructor(form) {
            super();
            if (form) {
                form.addEventListener = () => {};
                form.removeEventListener = () => {};
            }
        }
    };
    const visit = (method, url, options) => {
        if (options.onBefore?.({}) === false) return;
        options.onStart?.({});
        requests.push({ method, url, options });
    };
    context.mock.method(router, 'post', (url, data, options) =>
        visit('post', url, options),
    );
    context.mock.method(router, 'delete', (url, options) =>
        visit('delete', url, options),
    );
    const key = Symbol('dialog');
    const primitive = (name) =>
        vue.defineComponent({
            inheritAttrs: false,
            setup(_, { attrs, slots }) {
                return () => vue.h(name, attrs, slots.default?.());
            },
        });
    const Dialog = vue.defineComponent({
        props: { open: Boolean },
        emits: ['update:open'],
        setup(props, { emit, slots }) {
            vue.provide(key, {
                open: vue.toRef(props, 'open'),
                setOpen: (value) => emit('update:open', value),
            });
            return () =>
                vue.h('Dialog', { open: props.open }, slots.default?.());
        },
    });
    const control = (name, value) =>
        vue.defineComponent({
            setup(_, { slots }) {
                const dialog = vue.inject(key);
                return () =>
                    vue.h(
                        name,
                        { onClick: () => dialog.setOpen(value) },
                        slots.default?.(),
                    );
            },
        });
    const DialogContent = vue.defineComponent({
        inheritAttrs: false,
        setup(_, { attrs, slots }) {
            const dialog = vue.inject(key);
            return () =>
                dialog.open.value
                    ? vue.h('DialogContent', attrs, slots.default?.())
                    : null;
        },
    });
    const InputError = vue.defineComponent({
        props: { message: String },
        setup(props) {
            return () => vue.h('InputError', {}, props.message);
        },
    });
    const ui = mount(
        'resources/js/pages/officer/properties/PropertyRowActions.vue',
        { property: { ...property, ...overrides } },
        null,
        '/officer/properties',
        {
            '@inertiajs/vue3': { Form, Link: primitive('Link') },
            '@/components/InputError.vue': { default: InputError },
            '@/components/ui/dialog': {
                Dialog,
                DialogContent,
                DialogTrigger: control('DialogTrigger', true),
                DialogClose: control('DialogClose', false),
                ...Object.fromEntries(
                    [
                        'DialogHeader',
                        'DialogTitle',
                        'DialogDescription',
                        'DialogFooter',
                    ].map((name) => [name, primitive(name)]),
                ),
            },
        },
    );
    context.after(() => {
        ui.unmount();
        globalThis.FormData = previousFormData;
    });
    const submit = (index = 0) =>
        ui.all('form')[index].props.onSubmit({ preventDefault() {} });
    const finish = async (success = true, errors = {}) => {
        const request = requests.at(-1);
        if (success) await request.options.onSuccess?.({ props: {} });
        else request.options.onError?.(errors);
        request.options.onFinish?.({});
        await flush();
    };
    return {
        ...ui,
        requests,
        submit,
        finish,
        open: async () => {
            ui.all('DialogTrigger')[0].props.onClick();
            await flush();
        },
    };
}

void test('row has one Manage action and Cancel closes without a request', async (context) => {
    const ui = setup(context);
    assert.equal(ui.all('Button').length, 1);
    assert.match(ui.text(), /Manage/);
    assert.equal(ui.all('form').length, 0);
    await ui.open();
    assert.match(ui.text(), /Manage Property/);
    assert.match(ui.text(), /Block 2.*Lot 3/);
    assert.equal(
        ui.all('Link')[0].props.href.url,
        '/officer/properties/7/edit',
    );
    ui.all('DialogClose')[0].props.onClick();
    await flush();
    assert.equal(ui.all('form').length, 0);
    assert.equal(ui.requests.length, 0);
});

for (const active of [true, false]) {
    void test(`${active ? 'active' : 'inactive'} Property offers the matching lifecycle action`, async (context) => {
        const ui = setup(context, { is_active: active });
        await ui.open();
        const operation = active ? 'Deactivate' : 'Activate';
        assert.ok(
            ui.all('Button').find((node) => text(node).trim() === operation),
        );
        assert.equal(ui.all('form').length, 2);
        ui.submit();
        await flush();
        assert.equal(
            ui.requests[0].url,
            `/officer/properties/7/${operation.toLowerCase()}`,
        );
        assert.equal(ui.requests[0].method, 'post');
        await ui.finish();
        ui.values.property.is_active = !active;
        await ui.open();
        assert.ok(
            ui
                .all('Button')
                .find(
                    (node) =>
                        text(node).trim() ===
                        (active ? 'Activate' : 'Deactivate'),
                ),
        );
    });
}

void test('charged Property retains Edit and lifecycle action but cannot Delete', async (context) => {
    const ui = setup(context, { has_been_charged: true });
    await ui.open();
    assert.equal(ui.all('form').length, 1);
    assert.doesNotMatch(ui.text(), /Delete/);
    assert.equal(ui.all('Link').length, 1);
});

for (const operation of [0, 1]) {
    void test(`${operation === 0 ? 'lifecycle' : 'Delete'} submit blocks all repeated operations and closes on success`, async (context) => {
        const ui = setup(context);
        await ui.open();
        ui.submit(operation);
        ui.submit(operation);
        ui.submit(1 - operation);
        await flush();
        assert.equal(ui.requests.length, 1);
        assert.equal(ui.requests[0].options.preserveScroll, true);
        if (operation === 1)
            assert.match(
                ui.requests[0].url,
                /\/officer\/properties\/7\?_method=DELETE$/,
            );
        assert.ok(
            ui
                .all('Button')
                .filter((node) => node.props.type === 'submit')
                .every((node) => node.props.disabled),
        );
        assert.match(ui.text(), /Deactivating|Deleting/);
        assert.equal(
            ui.all('Button').find((node) => text(node).trim() === 'Cancel')
                .props.disabled,
            true,
        );
        ui.all('DialogClose')[0].props.onClick();
        await flush();
        assert.equal(ui.all('form').length, 2);
        await ui.finish();
        assert.equal(ui.all('form').length, 0);
    });
}

for (const operation of [0, 1]) {
    void test(`${operation === 0 ? 'lifecycle' : 'Delete'} validation failure stays open and can be retried`, async (context) => {
        const ui = setup(context);
        await ui.open();
        ui.submit(operation);
        await ui.finish(false, {
            property: 'This Property changed. Please retry.',
        });
        assert.equal(ui.all('form').length, 2);
        assert.match(ui.text(), /This Property changed. Please retry./);
        assert.ok(
            ui
                .all('Button')
                .filter((node) => node.props.type === 'submit')
                .every((node) => !node.props.disabled),
        );
        ui.submit(operation);
        await ui.finish();
        assert.equal(ui.requests.length, 2);
        assert.equal(ui.all('form').length, 0);
    });
}
