import assert from 'node:assert/strict';
import { test } from 'node:test';
import { mount } from './mount-vue.mjs';

const announcement = {
    id: 31,
    title: 'A gathering for our community',
    body: '<p>Everyone is welcome to join.</p>',
    published_at: '2026-10-14T00:00:00Z',
    is_pinned: true,
    attachments: [],
};

for (const isPublicVisitor of [true, false]) {
    void test(`articles use a standalone reading layout for ${isPublicVisitor ? 'guests' : 'signed-in readers'}`, (context) => {
        const ui = mount('resources/js/pages/announcements/Show.vue', {
            announcement,
            isPublicVisitor,
        });
        context.after(ui.unmount);

        assert.deepEqual(ui.layout, []);
        assert.equal(ui.all('main').length, 1);
        assert.equal(
            ui.all('article')[0].props['aria-labelledby'],
            'announcement-title',
        );
        assert.equal(ui.all('h1')[0].props.id, 'announcement-title');
        assert.match(ui.text(ui.all('h1')[0]), /A gathering for our community/);
        assert.match(ui.text(ui.all('time')[0]), /October 14, 2026/);
        assert.match(ui.text(), /Pinned/);
        assert.equal(
            ui.all('RichTextContent')[0].props.html,
            announcement.body,
        );
        const links = ui.all('Link').map((node) => node.props.href.url);
        assert.deepEqual(links, ['/']);
        const backButton = ui.all('Button', ui.all('main')[0])[0];
        assert.match(ui.text(backButton), /Back/);
        assert.equal(backButton.props.type, 'button');
        assert.equal(ui.all('AppSidebar').length, 0);
        assert.equal(ui.all('aside').length, 0);
    });
}

for (const historyLength of [1, 2]) {
    void test(`Back ${historyLength > 1 ? 'returns one history entry' : 'opens the feed when there is no previous entry'}`, (context) => {
        const historyBack = context.mock.fn();
        const visit = context.mock.fn();
        const originalWindow = globalThis.window;
        globalThis.window = {
            history: { length: historyLength, back: historyBack },
        };
        context.after(() => {
            if (originalWindow === undefined) delete globalThis.window;
            else globalThis.window = originalWindow;
        });
        const ui = mount(
            'resources/js/pages/announcements/Show.vue',
            {
                announcement,
                isPublicVisitor: true,
            },
            null,
            '/announcements/31',
            {
                '@inertiajs/vue3': { router: { visit } },
            },
        );
        context.after(ui.unmount);

        ui.all('Button', ui.all('main')[0])[0].props.onClick();

        assert.equal(historyBack.mock.callCount(), historyLength > 1 ? 1 : 0);
        assert.equal(visit.mock.callCount(), historyLength > 1 ? 0 : 1);
        if (historyLength === 1) {
            assert.equal(
                visit.mock.calls[0].arguments[0].url,
                '/announcements',
            );
        }
    });
}

void test('the first image leads the article without duplicating it in attachments', (context) => {
    const ui = mount('resources/js/pages/announcements/Show.vue', {
        isPublicVisitor: true,
        announcement: {
            ...announcement,
            attachments: [
                {
                    id: 1,
                    is_image: false,
                    url: '/announcements/attachments/1',
                    original_filename: 'Details.pdf',
                },
                {
                    id: 2,
                    is_image: true,
                    url: '/announcements/attachments/2',
                    original_filename: 'Gathering.jpg',
                },
                {
                    id: 3,
                    is_image: true,
                    url: '/announcements/attachments/3',
                    original_filename: 'Schedule.jpg',
                },
            ],
        },
    });
    context.after(ui.unmount);

    assert.equal(ui.all('figure').length, 1);
    assert.equal(
        ui.all('img', ui.all('figure')[0])[0].props.src,
        '/announcements/attachments/2',
    );
    assert.deepEqual(
        ui.all('img').map((node) => node.props.src),
        ['/announcements/attachments/2', '/announcements/attachments/3'],
    );
    assert.equal(ui.all('a')[0].props.href, '/announcements/attachments/1');
    assert.equal(ui.all('a')[0].props.rel, 'noopener noreferrer');
    assert.match(ui.text(ui.all('a')[0]), /Details.pdf/);
});

void test('articles without images or optional metadata keep a clean text layout', (context) => {
    const ui = mount('resources/js/pages/announcements/Show.vue', {
        isPublicVisitor: true,
        announcement: { ...announcement, is_pinned: false, published_at: null },
    });
    context.after(ui.unmount);

    assert.equal(ui.all('figure').length, 0);
    assert.equal(ui.all('img').length, 0);
    assert.equal(ui.all('time').length, 0);
    assert.equal(ui.all('Badge').length, 0);
    assert.equal(ui.all('section').length, 0);
    assert.equal(ui.all('RichTextContent')[0].props.html, announcement.body);
});
