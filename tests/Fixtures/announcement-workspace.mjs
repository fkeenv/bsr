import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { Form, useForm, router } from '@inertiajs/vue3';
import { mount } from './mount-vue.mjs';

const primitive = (name) =>
    vue.defineComponent({
        inheritAttrs: false,
        setup:
            (_, { attrs, slots }) =>
            () =>
                vue.h(name, attrs, slots.default?.()),
    });
const allGrants = [
    'announcements.read',
    'announcements.create',
    'announcements.edit',
    'announcements.publish',
    'announcements.pin',
    'announcements.attachments',
];
const notice = (id, published = false, pinned = false) => ({
    id,
    title: `Notice ${id}`,
    body: '<p>Rich text</p>',
    excerpt: 'Readable notice & details.',
    visibility: 'private',
    visibility_label: 'Private',
    is_published: published,
    is_pinned: pinned,
    published_at: published ? '2026-10-07T23:30:00+00:00' : null,
    updated_at: '2026-10-08T02:00:00+00:00',
    pinned_at: null,
    attachments: [],
});
const options = [
    {
        value: 'private',
        label: 'Private',
        description: 'Only eligible accounts.',
    },
    { value: 'public', label: 'Public', description: 'Anyone can read.' },
    {
        value: 'hidden',
        label: 'Hidden',
        description: 'The feed is unavailable.',
    },
];
function workspace(
    context,
    announcements = [notice(1), notice(2, true), notice(3, true, true)],
    grants = allGrants,
) {
    const page = vue.reactive({
        props: { auth: { announcementPermissions: [...grants] } },
    });
    const ui = mount(
        'resources/js/pages/officer/announcements/Index.vue',
        {
            announcements,
        },
        null,
        '/officer/announcements',
        {
            '@inertiajs/vue3': {
                Head: primitive('Head'),
                Link: primitive('Link'),
                Form,
                usePage: () => page,
            },
        },
    );
    context.after(ui.unmount);
    return { ...ui, page };
}
void test('status views show actual notices, local dates, attachments and clear empty results', async (context) => {
    const ui = workspace(context);
    assert.equal(ui.all('li').length, 3);
    assert.match(ui.text(), /Readable notice & details/);
    assert.match(ui.text(), /Published\s+Oct 8, 2026/);
    assert.match(ui.text(), /No attachments/);
    const select = async (label) => {
        ui.all('button')
            .find((node) => ui.text(node).trim() === label)
            .props.onClick();
        await vue.nextTick();
    };
    await select('Drafts');
    assert.deepEqual(
        ui.all('AnnouncementActions').map((node) => node.props.announcement.id),
        [1],
    );
    await select('Published');
    assert.equal(ui.all('li').length, 2);
    await select('Pinned');
    assert.deepEqual(
        ui.all('AnnouncementActions').map((node) => node.props.announcement.id),
        [3],
    );
    ui.values.announcements = [notice(1)];
    await vue.nextTick();
    assert.equal(ui.all('li').length, 0);
    assert.match(ui.text(), /No pinned Announcements/);
    await select('All');
    assert.equal(ui.all('li').length, 1);
    ui.values.announcements = [];
    await vue.nextTick();
    assert.match(ui.text(), /No Announcements yet/);
    assert.equal(ui.all('details').length, 0);
});

function actions(context, announcement, grants = allGrants) {
    const requests = [];
    context.mock.method(router, 'post', (url, data, callbacks) => {
        callbacks.onStart?.({});
        requests.push({ url, data, callbacks });
    });
    const page = vue.reactive({
        props: { auth: { announcementPermissions: [...grants] } },
    });
    const ui = mount(
        'resources/js/pages/officer/announcements/AnnouncementActions.vue',
        { announcement },
        null,
        '',
        {
            '@inertiajs/vue3': {
                useForm,
                Link: primitive('Link'),
                usePage: () => page,
            },
        },
    );
    context.after(ui.unmount);
    return { ...ui, requests, page };
}
for (const [published, pinned, labels] of [
    [false, false, ['Publish']],
    [true, false, ['Unpublish', 'Pin']],
    [true, true, ['Unpublish', 'Unpin']],
]) {
    void test(`notice actions follow published=${String(published)} pinned=${String(pinned)} state`, async (context) => {
        const ui = actions(context, notice(7, published, pinned));
        assert.deepEqual(
            ui.all('DropdownMenuItem').map((node) => ui.text(node).trim()),
            labels,
        );
        assert.equal(
            ui.all('Link')[0].props.href.url,
            '/officer/announcements/7/edit',
        );
        assert.match(
            ui.all('Button').find((node) => node.props['aria-label'])?.props[
                'aria-label'
            ],
            /Notice 7/,
        );
        for (const label of labels) {
            ui.all('DropdownMenu')[0].props['onUpdate:open'](true);
            await vue.nextTick();
            ui.all('DropdownMenuItem')
                .find((node) => ui.text(node).trim() === label)
                .props.onSelect({ preventDefault() {} });
            await vue.nextTick();
            assert.equal(
                ui.requests.at(-1).url,
                `/officer/announcements/7/${label.toLowerCase()}`,
            );
            assert.equal(ui.requests.at(-1).callbacks.preserveScroll, true);
            assert.ok(
                ui.all('DropdownMenuItem').every((node) => node.props.disabled),
            );
            await ui.requests.at(-1).callbacks.onSuccess?.({ props: {} });
            ui.requests.at(-1).callbacks.onFinish?.({});
            await vue.nextTick();
            assert.equal(ui.all('DropdownMenu')[0].props.open, false);
            assert.ok(
                ui
                    .all('DropdownMenuItem')
                    .every((node) => !node.props.disabled),
            );
        }
    });
}
void test('pin limit failure stays readable and a pending action cannot submit twice', async (context) => {
    const ui = actions(context, notice(7, true));
    ui.all('DropdownMenu')[0].props['onUpdate:open'](true);
    await vue.nextTick();
    const select = () =>
        ui
            .all('DropdownMenuItem')
            .find((node) => ui.text(node).trim() === 'Pin')
            .props.onSelect({ preventDefault() {} });
    select();
    select();
    await vue.nextTick();
    assert.equal(ui.requests.length, 1);
    assert.match(ui.text(), /Saving/);
    assert.ok(
        ui
            .all('p', ui.all('DropdownMenuContent')[0])
            .some((node) => node.props.role === 'status'),
    );
    assert.equal(ui.all('DropdownMenu')[0].props.open, true);
    ui.requests[0].callbacks.onError({
        pin: 'At most three published Announcements can be pinned.',
    });
    ui.requests[0].callbacks.onFinish({});
    await vue.nextTick();
    assert.equal(ui.all('DropdownMenu')[0].props.open, false);
    assert.ok(ui.all('div').some((node) => node.props.role === 'alert'));
    assert.equal(
        ui.all('InputError')[0].props.message,
        'At most three published Announcements can be pinned.',
    );
    select();
    await vue.nextTick();
    assert.equal(ui.requests.length, 2);
    assert.equal(ui.all('InputError').length, 0);
});

void test('each notice displays its own audience without global visibility controls', (context) => {
    const publicNotice = {
        ...notice(9, true),
        visibility: 'public',
        visibility_label: 'Public',
    };
    const ui = workspace(context, [notice(1), publicNotice]);
    assert.match(ui.text(), /Private/);
    assert.match(ui.text(), /Public/);
    assert.doesNotMatch(ui.text(), /Manage visibility|Save visibility/);
});

void test('excerpts remain text and attachment counts are based on each notice', (context) => {
    const announcement = notice(8);
    announcement.excerpt = '<img src=x onerror=alert(1)> is literal text';
    announcement.attachments = [{ id: 1 }];
    const ui = workspace(context, [announcement]);
    assert.equal(ui.all('img').length, 0);
    assert.match(ui.text(), /<img src=x onerror=alert\(1\)> is literal text/);
    assert.match(ui.text(), /1 attachment/);
    assert.ok(ui.all('p').every((node) => !node.props.innerHTML));
});

void test('the editor offers per-notice visibility, defaults to Private and exposes validation errors', async (context) => {
    const ui = mount(
        'resources/js/pages/officer/announcements/AnnouncementFormFields.vue',
        {
            body: '<p>Notice</p>',
            visibilityOptions: options,
            errors: {},
        },
    );
    context.after(ui.unmount);
    const radios = () =>
        ui.all('input').filter((node) => node.props.type === 'radio');
    assert.equal(radios().length, 3);
    assert.equal(
        radios().find((node) => node.props.checked).props.value,
        'private',
    );
    assert.ok(
        radios().every(
            (node) =>
                node.props.name === 'visibility' &&
                node.props.required !== undefined,
        ),
    );
    for (const option of options)
        assert.match(ui.text(), new RegExp(option.description));
    ui.values.visibility = 'hidden';
    ui.values.errors = { visibility: 'Choose a valid audience.' };
    await vue.nextTick();
    assert.equal(
        radios().find((node) => node.props.checked).props.value,
        'hidden',
    );
    assert.ok(ui.all('div').some((node) => node.props.role === 'alert'));
    assert.ok(
        ui
            .all('InputError')
            .some((node) => node.props.message === 'Choose a valid audience.'),
    );
});

void test('reading alone hides creation and mutation controls, including after grants change', async (context) => {
    const ui = workspace(context, [notice(1)], ['announcements.read']);
    assert.equal(
        ui
            .all('Link')
            .some(
                (link) =>
                    link.props.href.url === '/officer/announcements/create',
            ),
        false,
    );
    const row = actions(context, notice(2, true), [
        'announcements.read',
        'announcements.edit',
        'announcements.publish',
    ]);
    assert.equal(row.all('Link').length, 1);
    assert.deepEqual(
        row.all('DropdownMenuItem').map((item) => row.text(item).trim()),
        ['Unpublish'],
    );
    row.page.props.auth.announcementPermissions = ['announcements.read'];
    await vue.nextTick();
    assert.equal(row.all('Link').length, 0);
    assert.equal(row.all('DropdownMenu').length, 0);
});

void test('pin permission does not display an empty action menu for a draft', (context) => {
    const ui = actions(context, notice(1), [
        'announcements.read',
        'announcements.pin',
    ]);
    assert.equal(ui.all('DropdownMenu').length, 0);
});

function roleEditor(context) {
    const requests = [];
    context.mock.method(router, 'put', (url, data, callbacks) => {
        callbacks.onStart?.({});
        requests.push({ url, data, callbacks });
    });
    const ui = mount(
        'resources/js/pages/super-admin/role-permissions/RolePermissionEditor.vue',
        {
            role: {
                name: 'officer',
                label: 'Officer',
                grants: ['announcements.read'],
            },
            permissions: [
                {
                    value: 'announcements.read',
                    feature: 'Announcements',
                    label: 'Read staff Announcements',
                    description: 'Read the staff workspace.',
                },
                {
                    value: 'announcements.create',
                    feature: 'Announcements',
                    label: 'Create drafts',
                    description: 'Write new drafts.',
                },
            ],
        },
        null,
        '',
        { '@inertiajs/vue3': { useForm } },
    );
    context.after(ui.unmount);
    return { ...ui, requests };
}

void test('the shadcn editor submits selected grants, disables pending saves and exposes validation errors', async (context) => {
    const ui = roleEditor(context);
    assert.equal(
        ui.all('Button').find((button) => button.props.type === 'submit').props
            .disabled,
        true,
    );
    ui.all('Switch')
        .find((control) => control.props.id === 'officer-announcements.create')
        .props['onUpdate:modelValue'](true);
    await vue.nextTick();
    assert.equal(
        ui.all('Button').find((button) => button.props.type === 'submit').props
            .disabled,
        false,
    );
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    await vue.nextTick();
    assert.equal(ui.requests[0].url, '/super-admin/role-permissions/officer');
    assert.deepEqual(ui.requests[0].data.grants, [
        'announcements.read',
        'announcements.create',
    ]);
    assert.equal(
        ui.all('Button').find((button) => button.props.type === 'submit').props
            .disabled,
        true,
    );
    assert.ok(ui.all('Switch').every((control) => control.props.disabled));
    assert.ok(ui.all('Button').every((control) => control.props.disabled));
    ui.requests[0].callbacks.onError({
        grants: 'Choose an available permission.',
    });
    ui.requests[0].callbacks.onFinish({});
    await vue.nextTick();
    assert.equal(
        ui.all('InputError')[0].props.message,
        'Choose an available permission.',
    );
    assert.equal(
        ui.all('Button').find((button) => button.props.type === 'submit').props
            .disabled,
        false,
    );
});

void test('turning off all permissions stays a draft until saved and submits an empty grant list', async (context) => {
    const ui = roleEditor(context);
    ui.all('Button')
        .find(
            (button) =>
                button.props['aria-label'] ===
                'Disable all Officer permissions',
        )
        .props.onClick();
    await vue.nextTick();
    assert.equal(ui.requests.length, 0);
    assert.ok(
        ui
            .all('Switch')
            .every((control) => control.props['model-value'] === false),
    );
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    await vue.nextTick();
    assert.deepEqual(ui.requests[0].data.grants, []);
    assert.equal(ui.requests[0].url, '/super-admin/role-permissions/officer');
});

void test('unchanged server grants do not discard unsaved edits when another role is saved', async (context) => {
    const ui = roleEditor(context);
    ui.all('Switch')
        .find((control) => control.props.id === 'officer-announcements.create')
        .props['onUpdate:modelValue'](true);
    await vue.nextTick();
    ui.values.role = { ...ui.values.role, grants: ['announcements.read'] };
    await vue.nextTick();
    assert.equal(
        ui
            .all('Switch')
            .find(
                (control) =>
                    control.props.id === 'officer-announcements.create',
            ).props['model-value'],
        true,
    );
    assert.equal(
        ui.all('Button').find((button) => button.props.type === 'submit').props
            .disabled,
        false,
    );
});

void test('reading, editing and uploading expose their own page controls', (context) => {
    for (const [grants, contentForms, uploadForms] of [
        [['announcements.read'], 0, 0],
        [['announcements.read', 'announcements.edit'], 1, 0],
        [['announcements.read', 'announcements.attachments'], 0, 1],
    ]) {
        const page = vue.reactive({
            props: { auth: { announcementPermissions: grants } },
        });
        const formBoundary = vue.defineComponent({
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
            'resources/js/pages/officer/announcements/Edit.vue',
            {
                announcement: notice(7),
                visibilityOptions: options,
            },
            null,
            '',
            {
                '@inertiajs/vue3': {
                    usePage: () => page,
                    setLayoutProps: () => {},
                    Form: formBoundary,
                    Head: primitive('Head'),
                    Link: primitive('Link'),
                },
            },
        );
        context.after(ui.unmount);
        assert.equal(ui.all('AnnouncementFormFields').length, contentForms);
        assert.equal(
            ui.all('Input').filter((input) => input.props.type === 'file')
                .length,
            uploadForms,
        );
        assert.equal(ui.all('Form').length, contentForms + uploadForms);
        if (!contentForms)
            assert.equal(
                ui.all('RichTextContent')[0].props.html,
                '<p>Rich text</p>',
            );
    }
});
