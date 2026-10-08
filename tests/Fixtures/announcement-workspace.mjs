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
    const previousFormData = globalThis.FormData;
    globalThis.FormData = class extends previousFormData {
        constructor(form) {
            super();
            if (form) {
                form.addEventListener = () => {};
                form.removeEventListener = () => {};
                this.append('announcements_page_visibility', 'public');
            }
        }
    };
    context.after(() => {
        globalThis.FormData = previousFormData;
    });
    const ui = mount(
        'resources/js/pages/officer/announcements/Index.vue',
        {
            announcements,
            pageVisibility: 'private',
            pageVisibilityOptions: options,
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
    assert.equal(ui.all('details').length, 1);
    assert.equal(ui.all('details')[0].props.open, undefined);
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
    ui.requests[0].callbacks.onError({
        pin: 'At most three published Announcements can be pinned.',
    });
    ui.requests[0].callbacks.onFinish({});
    await vue.nextTick();
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

void test('visibility disclosure preserves server choices and Save feedback', async (context) => {
    const requests = [];
    context.mock.method(router, 'post', (url, data, callbacks) => {
        callbacks.onStart?.({});
        requests.push({ url, data, callbacks });
    });
    const ui = workspace(context, []);
    for (const option of options) {
        assert.match(ui.text(), new RegExp(option.label));
        assert.match(ui.text(), new RegExp(option.description));
    }
    assert.equal(
        ui.all('input').find((node) => node.props.checked).props.value,
        'private',
    );
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    await vue.nextTick();
    assert.equal(
        requests[0].url,
        '/officer/announcements-page-visibility?_method=PUT',
    );
    assert.equal(requests[0].data.announcements_page_visibility, 'public');
    assert.equal(requests[0].callbacks.preserveScroll, true);
    assert.ok(
        ui
            .all('Button')
            .some(
                (node) => node.props.type === 'submit' && node.props.disabled,
            ),
    );
    assert.match(ui.text(), /Saving visibility/);
    requests[0].callbacks.onError({
        announcements_page_visibility: 'Choose a valid visibility.',
    });
    requests[0].callbacks.onFinish({});
    await vue.nextTick();
    assert.ok(ui.all('div').some((node) => node.props.role === 'alert'));
    assert.equal(
        ui.all('InputError')[0].props.message,
        'Choose a valid visibility.',
    );
    assert.ok(
        ui
            .all('Button')
            .some(
                (node) => node.props.type === 'submit' && !node.props.disabled,
            ),
    );
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
