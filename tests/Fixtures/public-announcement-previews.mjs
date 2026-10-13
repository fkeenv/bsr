import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { mount } from './mount-vue.mjs';

const preview = {
    id: 31,
    title: 'Community gathering',
    published_at: '2026-10-14T00:00:00Z',
    excerpt: 'Join your neighbors for a community gathering.',
    image_url: '/announcements/attachments/17',
};
const fallback = '/images/announcements/community-stock.webp';

void test('cards link to the existing article and recover failed images decoratively', async (context) => {
    const ui = mount('resources/js/components/AnnouncementPreviewCard.vue', {
        preview: { ...preview },
    });
    context.after(ui.unmount);

    assert.match(ui.text(), /Community gathering/);
    assert.match(ui.text(), /October 14, 2026/);
    assert.match(ui.text(), /Join your neighbors/);
    assert.equal(ui.all('Link')[0].props.href.url, '/announcements/31');
    assert.equal(
        ui.all('Link')[0].props['aria-label'],
        'See more: Community gathering',
    );
    assert.equal(ui.all('img')[0].props.src, preview.image_url);

    ui.all('img')[0].props.onError();
    await vue.nextTick();
    assert.equal(ui.all('img')[0].props.src, fallback);
    assert.equal(ui.all('img')[0].props.alt, '');

    ui.values.preview.image_url = '/announcements/attachments/18';
    await vue.nextTick();
    assert.equal(ui.all('img')[0].props.src, '/announcements/attachments/18');
    ui.values.preview.image_url = null;
    await vue.nextTick();
    assert.equal(ui.all('img')[0].props.src, fallback);
});

function setupCarousel(context, previews) {
    const listeners = new Map();
    let position = 0;
    let lastPosition = previews.length - 1;
    let options;
    let plugins;
    const api = {
        on(event, listener) {
            listeners.set(event, listener);
            return api;
        },
        canScrollPrev: () => position > 0,
        canScrollNext: () => position < lastPosition,
        scrollPrev() {
            position = Math.max(0, position - 1);
            listeners.get('select')?.(api);
        },
        scrollNext() {
            position = Math.min(lastPosition, position + 1);
            listeners.get('select')?.(api);
        },
    };
    const ui = mount(
        'resources/js/components/PublicAnnouncementPreviews.vue',
        { previews },
        null,
        '/',
        {
            'embla-carousel-vue': {
                default: (opts, carouselPlugins) => {
                    options = opts;
                    plugins = carouselPlugins;
                    vue.onMounted(() =>
                        queueMicrotask(() => listeners.get('init')?.(api)),
                    );
                    return [vue.ref(), vue.ref(api)];
                },
            },
        },
        [
            '@/components/ui/carousel',
            './Carousel.vue',
            './CarouselContent.vue',
            './CarouselItem.vue',
            './CarouselNext.vue',
            './CarouselPrevious.vue',
            '@/components/AnnouncementPreviewCard.vue',
        ],
    );
    context.after(ui.unmount);
    return {
        ui,
        api,
        options,
        plugins,
        resize: () => {
            lastPosition = 0;
            position = 0;
            listeners.get('reInit')?.(api);
        },
    };
}

void test('empty previews hide the section and single cards omit navigation controls', (context) => {
    const empty = setupCarousel(context, []).ui;
    assert.equal(empty.all('section').length, 0);
    const single = setupCarousel(context, [{ ...preview }]).ui;
    assert.equal(single.all('section').length, 1);
    assert.equal(single.all('Button').length, 0);
    assert.equal(single.all('Link')[0].props.href.url, '/announcements');
});

void test('stock carousel connects keyboard and controls to Embla and responds to resize', async (context) => {
    const { ui, options, plugins, resize } = setupCarousel(
        context,
        Array.from({ length: 6 }, (_, index) => ({
            ...preview,
            id: index + 1,
        })),
    );
    await vue.nextTick();
    await vue.nextTick();
    const region = ui.all('div').find((node) => node.props.role === 'region');
    const buttons = () => ui.all('Button');
    assert.equal(region.props.tabindex, '0');
    assert.equal(region.props['aria-label'], 'Public announcements');
    assert.equal(buttons()[0].props.disabled, true);
    assert.equal(buttons()[1].props.disabled, false);
    assert.match(ui.text(buttons()[0]), /Previous Slide/);
    assert.match(ui.text(buttons()[1]), /Next Slide/);
    assert.equal(options.axis, 'x');
    assert.equal(options.loop, false);
    assert.notEqual(options.watchDrag, false);
    assert.equal(plugins, undefined);
    const slides = ui
        .all('div')
        .filter((node) => node.props['data-slot'] === 'carousel-item');
    assert.equal(slides.length, 6);
    for (const slide of slides) {
        assert.match(slide.props.class, /basis-full/);
        assert.match(slide.props.class, /md:basis-1\/2/);
        assert.match(slide.props.class, /lg:basis-1\/3/);
    }
    assert.ok(
        ui.all('div').some((node) => node.props.class?.includes('touch-pan-y')),
    );
    assert.ok(ui.all('img').every((node) => node.props.draggable === 'false'));

    let prevented = false;
    region.props.onKeydown({
        key: 'ArrowRight',
        preventDefault() {
            prevented = true;
        },
    });
    await vue.nextTick();
    assert.equal(prevented, true);
    assert.equal(buttons()[0].props.disabled, false);
    region.props.onKeydown({ key: 'ArrowLeft', preventDefault() {} });
    await vue.nextTick();
    assert.equal(buttons()[0].props.disabled, true);
    buttons()[1].props.onClick();
    buttons()[0].props.onClick();
    await vue.nextTick();
    assert.equal(buttons()[0].props.disabled, true);
    for (let index = 0; index < 5; index++) buttons()[1].props.onClick();
    await vue.nextTick();
    assert.equal(buttons()[1].props.disabled, true);
    resize();
    await vue.nextTick();
    assert.equal(buttons()[0].props.disabled, true);
    assert.equal(buttons()[1].props.disabled, true);
    assert.equal(ui.all('Link').length, 7);
});

void test('landing places previews between community and joining guidance', (context) => {
    const ui = mount('resources/js/pages/Welcome.vue', {
        canRegister: false,
        announcementPreviews: [preview],
    });
    context.after(ui.unmount);
    const children = ui.all('main')[0].children;
    const section = children.findIndex(
        (node) => node.type === 'PublicAnnouncementPreviews',
    );
    assert.equal(children[section - 1].props.id, 'essentials');
    assert.equal(children[section + 1].props.id, 'joining');
    assert.deepEqual(children[section].props.previews, [preview]);
});
