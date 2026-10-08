import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { Form, router } from '@inertiajs/vue3';
import { mount } from './mount-vue.mjs';
import { readFileSync } from 'node:fs';
import { compileStyle, parse } from '@vue/compiler-sfc';

const membership = (id, property, label, role = 'owner') => ({
    id,
    user_id: 9,
    property_id: property,
    property_label: label,
    role,
    started_at: '2026-10-01',
    ended_at: null,
});
function setup(
    context,
    memberships,
    capabilities = { isMembershipHolder: true },
    onboarding = null,
) {
    const page = vue.reactive({
        props: {
            auth: { user: { id: 9, name: 'Ana Cruz' }, capabilities },
            announcementsPageListed: true,
        },
    });
    const primitive = (name) =>
        vue.defineComponent({
            inheritAttrs: false,
            setup:
                (_, { attrs, slots }) =>
                () =>
                    vue.h(name, attrs, slots.default?.()),
        });
    const status = vue.ref('acknowledged');
    let tourOptions;
    let replays = 0;
    let retries = 0;
    const ui = mount(
        'resources/js/pages/Dashboard.vue',
        { memberships, onboarding },
        capabilities,
        '/dashboard',
        {
            '@inertiajs/vue3': {
                usePage: () => page,
                Head: primitive('Head'),
                Link: primitive('Link'),
            },
            '@/composables/useOnboardingTour': {
                useOnboardingTour: (options) => {
                    tourOptions = options;
                    return {
                        isRunning: vue.ref(false),
                        acknowledgementStatus: status,
                        replay: () => replays++,
                        retryAcknowledgement: () => retries++,
                    };
                },
            },
        },
    );
    context.after(ui.unmount);
    return {
        ...ui,
        page,
        status,
        replays: () => replays,
        retries: () => retries,
        steps: (mobile) =>
            tourOptions.steps({
                isMobile: mobile,
                revealNavigationSection: () => () => {},
            }),
    };
}

void test('live Memberships become labeled Property cards with everyday destinations', (context) => {
    const ui = setup(context, [
        membership(1, 7, 'Block 3 · Lot 12'),
        membership(2, 8, 'Block 8 · Lot 4', 'resident'),
    ]);
    assert.match(ui.text(), /Welcome back\s*, Ana Cruz/);
    assert.match(ui.text(), /Your Properties/);
    const cards = ui.all('article');
    assert.equal(cards.length, 2);
    for (const [index, property] of [7, 8].entries()) {
        const links = ui
            .all('Link', cards[index])
            .map((node) => node.props.href.url);
        assert.ok(
            links.includes(`/properties/${property}/statement-of-account`),
        );
        assert.ok(links.includes(`/properties/${property}/profile`));
    }
    assert.match(ui.text(cards[0]), /Owner Membership/);
    assert.match(ui.text(cards[1]), /Resident Membership/);
    assert.equal(ui.all('DataTable').length, 0);
});

void test('getting started is quiet but can be revealed by the tour, with hidden Announcements omitted', async (context) => {
    const onboarding = {
        experience: 'member',
        version: 1,
        tour_acknowledged: true,
        steps: ['announcements', 'statement-of-account', 'property-profile'],
        completed_steps: ['announcements'],
    };
    const ui = setup(
        context,
        [membership(1, 7, 'Block 3 · Lot 12')],
        { isMembershipHolder: true },
        onboarding,
    );
    assert.equal(ui.all('details')[0].props.open, false);
    assert.match(ui.text(ui.all('summary')[0]), /Continue getting started/);
    const checklist = ui.all('OnboardingChecklist')[0];
    assert.equal(checklist.props.items.length, 3);
    assert.equal(checklist.props.items[0].isComplete, true);
    assert.equal(checklist.props.items[1].link.href.method, 'post');
    assert.deepEqual(checklist.props.items[1].link.data, {
        step: 'statement-of-account',
    });
    assert.equal(
        checklist.props.items[2].link.href.url,
        '/properties/7/profile',
    );
    const step = ui
        .steps(false)
        .find((step) => step.target === 'member-checklist');
    await step.prepare(new AbortController().signal);
    await vue.nextTick();
    assert.equal(ui.all('details')[0].props.open, true);
    ui.page.props.announcementsPageListed = false;
    await vue.nextTick();
    assert.equal(
        ui
            .all('OnboardingChecklist')[0]
            .props.items.some((item) => item.key === 'announcements'),
        false,
    );
    assert.equal(
        ui
            .steps(false)
            .find((step) => step.target === 'nav-membership')
            .description.includes('Announcements'),
        false,
    );
    ui.all('Button')
        .find((node) => node.props['data-tour'] === 'member-help')
        .props.onClick();
    assert.equal(ui.replays(), 1);
    ui.status.value = 'failed';
    await vue.nextTick();
    assert.ok(ui.all('div').some((node) => node.props.role === 'alert'));
    ui.all('Button')
        .find((node) => ui.text(node).trim() === 'Retry')
        .props.onClick();
    assert.equal(ui.retries(), 1);
});

for (const superAdmin of [false, true]) {
    void test(`no-Membership accounts ${superAdmin ? 'retain association guidance without joining' : 'can follow invitation guidance and join'}`, (context) => {
        const ui = setup(context, [], {
            isMembershipHolder: false,
            isSuperAdmin: superAdmin,
        });
        assert.equal(ui.all('article').length, 0);
        assert.match(ui.text(), /A place for your Properties/);
        const joins = ui
            .all('Link')
            .filter((node) => node.props.href.url === '/join-property');
        assert.equal(joins.length, superAdmin ? 0 : 1);
        assert.equal(ui.all('DashboardMembershipRowActions').length, 0);
        if (superAdmin)
            assert.match(ui.text(), /association tools are in the navigation/);
        else
            assert.match(ui.text(), /Ask an Officer for a Property Invitation/);
    });
}

void test('Property actions respect capabilities and Membership ownership', (context) => {
    for (const [capabilities, statements, profiles, management] of [
        [{ isMembershipHolder: false, canAccessOfficer: false }, 0, 0, 0],
        [{ isMembershipHolder: false, canAccessOfficer: true }, 0, 1, 0],
        [{ isMembershipHolder: true, isSuperAdmin: true }, 1, 1, 1],
    ]) {
        const ui = setup(
            context,
            [membership(1, 7, 'Block 3 · Lot 12')],
            capabilities,
        );
        assert.equal(
            ui
                .all('Link')
                .filter((node) =>
                    node.props.href.url.endsWith('/statement-of-account'),
                ).length,
            statements,
        );
        assert.equal(
            ui
                .all('Link')
                .filter((node) => node.props.href.url.endsWith('/profile'))
                .length,
            profiles,
        );
        assert.equal(
            ui.all('DashboardMembershipRowActions').length,
            management,
        );
    }
    const ui = setup(context, [
        { ...membership(1, 7, 'Other Property'), user_id: 18 },
    ]);
    assert.equal(ui.all('DashboardMembershipRowActions').length, 0);
});

void test('long and missing Property labels stay in identifiable cards without invented financial data', (context) => {
    const label = 'A'.repeat(220);
    const ui = setup(context, [
        membership(1, 7, label),
        membership(2, 8, null),
    ]);
    assert.match(ui.text(), new RegExp(label));
    assert.match(ui.text(), /Property 8/);
    assert.equal(ui.all('h3').length, 2);
    for (const card of ui.all('article'))
        assert.ok(
            ui
                .all('h3', card)
                .some(
                    (node) => node.props.id === card.props['aria-labelledby'],
                ),
        );
    assert.doesNotMatch(
        ui.text(),
        /Outstanding Balance|₱|Recent Announcements/,
    );
    assert.equal(
        ui.steps(true).some((step) => step.target === 'member-checklist'),
        false,
    );
    assert.equal(
        ui.steps(true).some((step) => step.target === 'nav-membership'),
        false,
    );
});

function membershipManagement(context) {
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
    context.mock.method(router, 'post', (url, data, options) => {
        options.onStart?.({});
        requests.push({ url, data, options });
    });
    const primitive = (name) =>
        vue.defineComponent({
            inheritAttrs: false,
            setup:
                (_, { attrs, slots }) =>
                () =>
                    vue.h(name, attrs, slots.default?.()),
        });
    const key = Symbol('membership-dialog');
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
            setup: (_, { slots }) => {
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
        setup: (_, { slots }) => {
            const dialog = vue.inject(key);
            return () =>
                dialog.open.value
                    ? vue.h('DialogContent', {}, slots.default?.())
                    : null;
        },
    });
    const InputError = vue.defineComponent({
        props: { message: String },
        setup: (props) => () => vue.h('InputError', {}, props.message),
    });
    const ui = mount(
        'resources/js/pages/DashboardMembershipRowActions.vue',
        { membership: membership(4, 7, 'Block 3 · Lot 12') },
        null,
        '/dashboard',
        {
            '@inertiajs/vue3': { Form },
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
    return {
        ...ui,
        requests,
        open: async () => {
            ui.all('DialogTrigger')[0].props.onClick();
            await vue.nextTick();
        },
        submit: () => ui.all('form')[0].props.onSubmit({ preventDefault() {} }),
        finish: async (success = true) => {
            const request = requests.at(-1);
            if (success) await request.options.onSuccess?.({ props: {} });
            else
                request.options.onError?.({
                    membership: 'This Membership has already ended.',
                });
            request.options.onFinish?.({});
            await vue.nextTick();
        },
    };
}

void test('Manage Membership opens confirmation and Cancel leaves the Membership alone', async (context) => {
    const ui = membershipManagement(context);
    assert.match(ui.text(), /Manage Membership/);
    assert.equal(ui.all('form').length, 0);
    await ui.open();
    assert.match(ui.text(), /End Membership/);
    assert.match(ui.text(), /Block 3 · Lot 12/);
    assert.equal(ui.requests.length, 0);
    ui.all('DialogClose')[0].props.onClick();
    await vue.nextTick();
    assert.equal(ui.all('form').length, 0);
    assert.equal(ui.requests.length, 0);
});

void test('End Membership submits the selected Membership and closes only on success', async (context) => {
    const ui = membershipManagement(context);
    await ui.open();
    ui.submit();
    await vue.nextTick();
    assert.equal(ui.requests[0].url, '/memberships/4/end');
    assert.equal(ui.requests[0].options.preserveScroll, true);
    assert.equal(
        ui.all('Button').find((button) => button.props.type === 'submit').props
            .disabled,
        true,
    );
    await ui.finish(false);
    assert.equal(ui.all('form').length, 1);
    assert.match(ui.text(), /This Membership has already ended/);
    ui.submit();
    await ui.finish();
    assert.equal(ui.all('form').length, 0);
});

void test('dashboard styles do not override application appearance at the root', () => {
    const source = readFileSync(
        new URL('../../resources/js/pages/Dashboard.vue', import.meta.url),
        'utf8',
    );
    const { descriptor } = parse(source);
    const applicationRoots = new Set([
        ':root',
        'html',
        'body',
        '.dark',
        '.light',
    ]);

    for (const style of descriptor.styles) {
        const compiled = compileStyle({
            source: style.content,
            filename: 'Dashboard.vue',
            id: 'data-v-dashboard-test',
            scoped: style.scoped,
        });
        assert.deepEqual(compiled.errors, []);
        for (const [, selector, declarations] of compiled.code.matchAll(
            /([^{}]+)\{([^{}]+)\}/g,
        )) {
            if (
                !/--(?:background|foreground|primary|card|accent|border|ring)\s*:/.test(
                    declarations,
                )
            )
                continue;
            for (const target of selector.split(',')) {
                const element = target
                    .trim()
                    .split(/[\s>+~]+/)
                    .at(-1);
                assert.equal(
                    applicationRoots.has(element),
                    false,
                    `Application appearance override: ${target}`,
                );
            }
        }
    }
});
