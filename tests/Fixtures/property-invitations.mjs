import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { mount } from './mount-vue.mjs';

const properties = [
    { id: 1, label: 'Block 12 · Lot 8' },
    { id: 2, label: 'Block 3 · Lot 12' },
    { id: 3, label: 'Block 12 · Lot 9' },
];

void test('Property picker searches labels, emits only selections, and preserves the selected label', async (context) => {
    const selected = [];
    const ui = mount(
        'resources/js/components/PropertyInvitationPropertyPicker.vue',
        {
            id: 'property_id',
            properties,
            modelValue: '',
            'onUpdate:modelValue': (value) => {
                selected.push(value);
                ui.values.modelValue = value;
            },
        },
    );
    context.after(ui.unmount);
    ui.all('ComboboxInput')[0].props['onUpdate:modelValue']('12 8');
    await vue.nextTick();
    assert.deepEqual(
        ui.all('ComboboxItem').map((node) => node.props.value),
        [1],
    );
    assert.deepEqual(selected, []);
    ui.all('Combobox')[0].props['onUpdate:modelValue'](1);
    await vue.nextTick();
    assert.deepEqual(selected, [1]);
    assert.match(ui.text(ui.all('Button')[0]), /Block 12 · Lot 8/);
    ui.values.modelValue = '';
    await vue.nextTick();
    assert.match(ui.text(ui.all('Button')[0]), /Select a Property/);
});

void test('Property picker reports no matches and disables an empty roster', async (context) => {
    const ui = mount(
        'resources/js/components/PropertyInvitationPropertyPicker.vue',
        { id: 'property_id', properties, modelValue: '' },
    );
    context.after(ui.unmount);
    ui.all('ComboboxInput')[0].props['onUpdate:modelValue']('missing');
    await vue.nextTick();
    assert.equal(ui.all('ComboboxItem').length, 0);
    assert.match(ui.text(), /No Properties match/);
    ui.values.properties = [];
    await vue.nextTick();
    assert.equal(ui.all('Combobox')[0].props.disabled, true);
    assert.equal(ui.all('ComboboxInput')[0].props.disabled, true);
});

void test('Officer creation submits the selected Property, resets the picker, and copies either credential', async (context) => {
    const { http } = await import('@inertiajs/core');
    const { useHttp } = await import('@inertiajs/vue3');
    const requests = [];
    const copied = [];
    const previousClient = http.getClient();
    const previousNavigator = Object.getOwnPropertyDescriptor(
        globalThis,
        'navigator',
    );
    Object.defineProperty(globalThis, 'navigator', {
        configurable: true,
        value: {
            clipboard: {
                writeText: async (value) => {
                    copied.push(value);
                },
            },
        },
    });
    http.setClient({
        request: (config) =>
            new Promise((resolve) => requests.push({ config, resolve })),
    });
    context.after(() => {
        http.setClient(previousClient);
        if (previousNavigator)
            Object.defineProperty(globalThis, 'navigator', previousNavigator);
        else delete globalThis.navigator;
    });
    const reloads = [];
    const ui = mount(
        'resources/js/pages/officer/property-invitations/Index.vue',
        { properties, invitations: [], table: { dateRanges: [], values: {} } },
        null,
        '/officer/property-invitations',
        {
            '@inertiajs/vue3': {
                Head: vue.defineComponent({ setup: () => () => null }),
                useHttp,
                router: { reload: (options) => reloads.push(options) },
            },
        },
    );
    context.after(ui.unmount);
    const picker = ui.all('PropertyInvitationPropertyPicker')[0];
    picker.props['onUpdate:modelValue'](2);
    await vue.nextTick();
    assert.equal(requests.length, 0);
    ui.all('Select')[0].props['onUpdate:modelValue']('resident');
    const submission = ui
        .all('form')[0]
        .props.onSubmit({ preventDefault() {} });
    assert.equal(JSON.parse(requests[0].config.data).property_id, 2);
    assert.equal(JSON.parse(requests[0].config.data).role, 'resident');
    requests[0].resolve({
        status: 201,
        data: JSON.stringify({
            url: 'https://example.test/invitation',
            code: '121bd641-2514-46ce-a6dd-b7b8a246a1ff',
        }),
        headers: {},
    });
    await submission;
    await vue.nextTick();
    assert.equal(
        ui.all('PropertyInvitationPropertyPicker')[0].props.modelValue,
        '',
    );
    assert.deepEqual(reloads, [{ only: ['invitations'] }]);
    for (const label of ['Copy link', 'Copy code']) {
        await ui
            .all('Button')
            .find((node) => ui.text(node).trim() === label)
            .props.onClick();
    }
    assert.deepEqual(copied, [
        'https://example.test/invitation',
        '121bd641-2514-46ce-a6dd-b7b8a246a1ff',
    ]);
    globalThis.navigator.clipboard.writeText = async () => {
        throw new Error('Clipboard unavailable');
    };
    await ui
        .all('Button')
        .find((node) => ui.text(node).trim() === 'Copy code')
        .props.onClick();
    await vue.nextTick();
    assert.match(ui.text(), /Copy failed/);
    assert.equal(
        ui.all('Input').find((node) => node.props.id === 'issued-code').props[
            'model-value'
        ],
        '121bd641-2514-46ce-a6dd-b7b8a246a1ff',
    );
});

void test('Join a Property validates and normalizes a code while keeping invitation links usable', async (context) => {
    const { useForm, router } = await import('@inertiajs/vue3');
    const posts = [];
    const visits = [];
    const previousPost = router.post.bind(router);
    router.post = (url, data, options) => {
        posts.push({ url, data, options });
    };
    context.after(() => {
        router.post = previousPost;
    });
    const ui = mount(
        'resources/js/pages/join-property/Show.vue',
        {},
        null,
        '/join-property',
        {
            '@inertiajs/vue3': {
                Head: vue.defineComponent({ setup: () => () => null }),
                useForm,
                router: { visit: (route) => visits.push(route) },
            },
        },
    );
    context.after(ui.unmount);
    const input = ui
        .all('Input')
        .find((node) => node.props.id === 'invitation_code');
    input.props['onUpdate:modelValue']('short');
    await vue.nextTick();
    ui.all('form')[1].props.onSubmit({ preventDefault() {} });
    await vue.nextTick();
    assert.equal(posts.length, 0);
    assert.ok(
        ui
            .all('InputError')
            .some((node) =>
                /full invitation code/.test(node.props.message ?? ''),
            ),
    );
    input.props['onUpdate:modelValue'](
        '  121BD641-2514-46CE-A6DD-B7B8A246A1FF  ',
    );
    await vue.nextTick();
    ui.all('form')[1].props.onSubmit({ preventDefault() {} });
    assert.equal(posts[0].url, '/property-invitation-codes');
    assert.equal(posts[0].data.code, '121bd641-2514-46ce-a6dd-b7b8a246a1ff');
    posts[0].options.onSuccess({});
    await vue.nextTick();
    assert.equal(input.props.modelValue, '');
    const token = 'a'.repeat(64);
    ui.all('Input')
        .find((node) => node.props.id === 'invitation_link')
        .props['onUpdate:modelValue'](
            `https://example.test/property-invitations/${token}`,
        );
    await vue.nextTick();
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    assert.equal(visits[0].url, `/property-invitations/${token}`);
});

void test('Manage retrieves credentials on demand, supports manual copying, and closes after revocation', async (context) => {
    const { http } = await import('@inertiajs/core');
    const { useHttp } = await import('@inertiajs/vue3');
    const previousClient = http.getClient();
    const previousNavigator = Object.getOwnPropertyDescriptor(
        globalThis,
        'navigator',
    );
    const requests = [];
    const copied = [];
    const notifications = [];
    Object.defineProperty(globalThis, 'navigator', {
        configurable: true,
        value: {
            clipboard: { writeText: async (value) => copied.push(value) },
        },
    });
    http.setClient({
        request: (config) =>
            new Promise((resolve, reject) =>
                requests.push({ config, resolve, reject }),
            ),
    });
    context.after(() => {
        http.setClient(previousClient);
        if (previousNavigator)
            Object.defineProperty(globalThis, 'navigator', previousNavigator);
        else delete globalThis.navigator;
    });
    const Form = vue.defineComponent({
        setup(_, { attrs, slots }) {
            return () =>
                vue.h('Form', attrs, slots.default({ processing: false }));
        },
    });
    const ui = mount(
        'resources/js/pages/officer/property-invitations/InvitationRowActions.vue',
        {
            invitation: {
                id: 9,
                property_label: 'Block 1 · Lot 2',
                role: 'owner',
                can_revoke: true,
                can_share: true,
                sharing_unavailable_reason: null,
            },
        },
        null,
        '/officer/property-invitations',
        {
            '@inertiajs/vue3': { Form, useHttp },
            'vue-sonner': {
                toast: {
                    success: (title, options) =>
                        notifications.push({
                            type: 'success',
                            title,
                            ...options,
                        }),
                    error: (title, options) =>
                        notifications.push({
                            type: 'error',
                            title,
                            ...options,
                        }),
                },
            },
        },
    );
    context.after(ui.unmount);
    ui.all('Dialog')[0].props['onUpdate:open'](true);
    await vue.nextTick();
    assert.equal(requests.length, 0);
    const click = (label) =>
        ui
            .all('Button')
            .find((node) => ui.text(node).trim() === label)
            .props.onClick();
    const credentials = {
        url: 'https://example.test/property-invitations/original',
        code: '121bd641-2514-46ce-a6dd-b7b8a246a1ff',
    };
    const originalInputs = ui.all('Input');
    assert.equal(ui.all('Alert').length, 0);
    assert.ok(
        !ui.all('div').some((node) => node.props.class?.includes('min-h-20')),
        'No empty notification space before copying',
    );
    assert.equal(
        originalInputs.length,
        2,
        'Sharing fields keep the dialog height stable before copying',
    );
    for (const [label, key] of [
        ['Copy link', 'url'],
        ['Copy code', 'code'],
    ]) {
        const pending = click(label);
        await vue.nextTick();
        if (key === 'code')
            assert.equal(
                ui.all('Alert').length,
                1,
                'Keep the previous confirmation visible during another copy',
            );
        const pendingInputs = ui.all('Input');
        assert.equal(pendingInputs.length, 2);
        assert.deepEqual(
            pendingInputs.map((node) => node.props.id),
            originalInputs.map((node) => node.props.id),
            'Copying must keep both fields in place',
        );
        assert.match(
            requests.at(-1).config.url,
            /property-invitations\/9\/share/,
        );
        requests.at(-1).resolve({
            status: 200,
            data: JSON.stringify(credentials),
            headers: {},
        });
        await pending;
        await vue.nextTick();
        assert.equal(copied.at(-1), credentials[key]);
        assert.match(ui.text(), /Copied/);
        assert.equal(
            notifications.length,
            0,
            'Copy feedback stays inside Manage without a toast',
        );
        const message = ui.all('Alert')[0];
        assert.equal(message.props.role, 'status');
        assert.match(ui.text(message), /You can now paste it into a message/);
        assert.match(message.props.class, /bg-green-50/);
        assert.match(message.props.class, /dark:bg-green-950/);
        assert.match(message.props.class, /text-green-950/);
        assert.match(message.props.class, /dark:text-green-100/);
        const content = ui.all('DialogContent')[0];
        assert.ok(
            ui.text(content).indexOf('Copied invitation') <
                ui.text(content).indexOf('Invitation link'),
        );
    }
    globalThis.navigator.clipboard.writeText = async () => {
        throw new Error('Unavailable');
    };
    const pending = click('Copy code');
    requests.at(-1).resolve({
        status: 200,
        data: JSON.stringify(credentials),
        headers: {},
    });
    await pending;
    await vue.nextTick();
    assert.match(ui.text(), /select and copy/);
    assert.equal(notifications.length, 0);
    assert.match(ui.all('Alert')[0].props.class, /bg-red-50/);
    assert.equal(
        ui.all('Input').find((node) => node.props.id === 'share-code-9').props[
            'model-value'
        ],
        credentials.code,
    );
    const denied = click('Copy link');
    requests.at(-1).resolve({
        status: 422,
        headers: {},
        data: JSON.stringify({
            errors: {
                invitation: [
                    'This invitation is revoked and can no longer be shared.',
                ],
            },
        }),
    });
    await denied;
    await vue.nextTick();
    assert.match(ui.text(), /revoked and can no longer be shared/);
    assert.ok(
        ui.all('Input').every((node) => node.props['model-value'] === ''),
    );
    const late = click('Copy link');
    ui.all('Dialog')[0].props['onUpdate:open'](false);
    await vue.nextTick();
    const previousCopies = copied.length;
    const previousNotifications = notifications.length;
    requests.at(-1).resolve({
        status: 200,
        data: JSON.stringify(credentials),
        headers: {},
    });
    await late;
    await vue.nextTick();
    assert.equal(copied.length, previousCopies);
    assert.equal(notifications.length, previousNotifications);
    assert.ok(
        ui.all('Input').every((node) => node.props['model-value'] === ''),
    );
    ui.all('Dialog')[0].props['onUpdate:open'](true);
    await vue.nextTick();
    ui.all('Form')[0].props.onSuccess();
    await vue.nextTick();
    assert.equal(ui.all('Dialog')[0].props.open, false);
    assert.ok(
        ui.all('Input').every((node) => node.props['model-value'] === ''),
    );
    ui.values.invitation = {
        ...ui.values.invitation,
        can_share: false,
        sharing_unavailable_reason:
            'The original link cannot be recovered. Revoke it and create a replacement.',
    };
    await vue.nextTick();
    assert.match(ui.text(), /original link cannot be recovered/);
    assert.equal(
        ui.all('Button').some((node) => ui.text(node).includes('Copy')),
        false,
    );
});
