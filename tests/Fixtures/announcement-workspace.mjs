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
) {
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
            },
        },
    );
    context.after(ui.unmount);
    return ui;
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

function actions(context, announcement) {
    const requests = [];
    context.mock.method(router, 'post', (url, data, callbacks) => {
        callbacks.onStart?.({});
        requests.push({ url, data, callbacks });
    });
    const ui = mount(
        'resources/js/pages/officer/announcements/AnnouncementActions.vue',
        { announcement },
        null,
        '',
        {
            '@inertiajs/vue3': { useForm, Link: primitive('Link') },
        },
    );
    context.after(ui.unmount);
    return { ...ui, requests };
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
