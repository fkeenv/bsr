import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { mount } from './mount-vue.mjs';

const member = {
    isMembershipHolder: true,
    isSuperAdmin: false,
    canAccessOfficer: false,
    canAccessAdministrator: false,
};
const primitive = (name) =>
    vue.defineComponent({
        inheritAttrs: false,
        setup:
            (_, { attrs, slots }) =>
            () =>
                vue.h(name, attrs, slots.default?.()),
    });
function setup(
    context,
    capabilities,
    url = '/dashboard',
    mobile = false,
    collapsed = false,
) {
    const page = vue.reactive({
        url,
        props: {
            auth: { capabilities },
            announcementsPageListed: true,
        },
    });
    const openMobile = vue.ref(mobile);
    const handlers = new Map();
    const sidebar = new Proxy(
        {
            useSidebar: () => ({
                isMobile: vue.ref(mobile),
                state: vue.ref(collapsed ? 'collapsed' : 'expanded'),
                setOpenMobile: (value) => {
                    openMobile.value = value;
                },
            }),
        },
        { get: (target, name) => target[name] ?? primitive(name) },
    );
    const ui = mount(
        'resources/js/components/AppSidebar.vue',
        {},
        capabilities,
        url,
        {
            '@inertiajs/vue3': {
                usePage: () => page,
                Link: primitive('Link'),
                router: {
                    on: (event, handler) => {
                        handlers.set(event, handler);
                        return () => handlers.delete(event);
                    },
                },
            },
            '@/components/ui/sidebar': sidebar,
        },
    );
    context.after(ui.unmount);
    return {
        ...ui,
        page,
        openMobile,
        handlers,
        destinations: () => ui.all('Link').map((node) => node.props.href?.url),
    };
}

void test('members keep statement and listed announcements without privileged links', async (context) => {
    const ui = setup(context, member);
    assert.ok(ui.destinations().includes('/statement-of-account'));
    assert.ok(ui.destinations().includes('/announcements'));
    assert.equal(
        ui.destinations().some((url) => url.startsWith('/officer')),
        false,
    );
    ui.page.props.announcementsPageListed = false;
    await vue.nextTick();
    assert.equal(ui.destinations().includes('/announcements'), false);
    assert.match(ui.text(), /Blessed Sacrament/);
    assert.match(ui.text(), /Homeowners Association/);
});

void test('guests and non-members can reach the feed filtered for public notices', (context) => {
    for (const capabilities of [
        null,
        { ...member, isMembershipHolder: false },
    ]) {
        const ui = setup(context, capabilities);
        assert.ok(ui.destinations().includes('/announcements'));
        assert.equal(
            ui.destinations().includes('/statement-of-account'),
            false,
        );
    }
});

void test('platform roles inherit all permitted workspaces and association settings', (context) => {
    for (const capabilities of [
        { ...member, isMembershipHolder: false, canAccessOfficer: true },
        {
            ...member,
            isMembershipHolder: false,
            canAccessOfficer: true,
            canAccessAdministrator: true,
        },
        {
            ...member,
            isMembershipHolder: false,
            canAccessOfficer: true,
            canAccessAdministrator: true,
            isSuperAdmin: true,
        },
    ]) {
        const ui = setup(context, capabilities, '/officer');
        for (const path of [
            '/officer',
            '/officer/announcements',
            '/officer/unpaid',
            '/officer/memberships',
            '/officer/property-invitations',
            '/officer/payments',
            '/officer/properties',
            '/officer/fee-types',
            '/officer/suspends',
            '/officer/charges',
            '/officer/charges/generate',
            '/officer/levy-settings',
            '/officer/bill-settings',
        ])
            assert.ok(ui.destinations().includes(path), path);
        assert.equal(
            ui.destinations().includes('/administrator/officers'),
            capabilities.canAccessAdministrator,
        );
        assert.equal(
            ui.destinations().includes('/super-admin/administrators'),
            capabilities.isSuperAdmin,
        );
        assert.equal(
            ui.destinations().includes('/join-property'),
            !capabilities.isSuperAdmin,
        );
        assert.match(ui.text(), /Association settings/);
        assert.match(ui.text(), /Properties & people/);
        if (capabilities.isSuperAdmin) {
            assert.ok(
                ui.destinations().includes('/super-admin/terms-of-service'),
            );
            assert.ok(
                ui.destinations().includes('/super-admin/privacy-policy'),
            );
        }
        assert.equal(ui.all('NavFooter').length, 0);
        assert.equal(ui.all('NavUser').length, 1);
        assert.ok(
            ui
                .all('AccordionItem')
                .some((node) => node.props['data-tour'] === 'nav-officer'),
        );
    }
});

void test('mobile navigation closes for visits and history navigation and unregisters on unmount', async (context) => {
    const ui = setup(context, member, '/dashboard', true);
    ui.handlers.get('start')();
    assert.equal(ui.openMobile.value, false);
    ui.openMobile.value = true;
    ui.handlers.get('navigate')();
    assert.equal(ui.openMobile.value, false);
    ui.unmount();
    assert.equal(ui.handlers.size, 0);
});

void test('statement detail routes reveal the member section while settings highlight their destination', (context) => {
    const ui = setup(context, member, '/statement-of-account/4?year=2026');
    assert.equal(ui.all('Accordion')[0].props.modelValue, 'membership');
    const officer = setup(
        context,
        { ...member, canAccessOfficer: true },
        '/officer/levy-settings',
    );
    const details = officer.all('details');
    assert.equal(details.length, 0);
    assert.equal(officer.all('Accordion')[0].props.modelValue, 'officer');
    const active = officer
        .all('Link')
        .find((node) => node.props['aria-current'] === 'page');
    assert.equal(active.props.href.url, '/officer/levy-settings');
});

void test('mobile drawer exposes a close control and restores focus to its opener', (context) => {
    let sidebarContext;
    let focusCount = 0;
    const opener = { isConnected: true, focus: () => focusCount++ };
    const mobile = vue.ref(true);
    const provider = mount(
        'resources/js/components/ui/sidebar/SidebarProvider.vue',
        {},
        null,
        '/dashboard',
        {
            '@vueuse/core': {
                defaultDocument: { cookie: '', activeElement: opener },
                useMediaQuery: () => mobile,
                useEventListener: () => {},
                useVModel: () => vue.ref(true),
            },
            './utils': {
                SIDEBAR_COOKIE_NAME: 'sidebar_state',
                provideSidebarContext: (value) => {
                    sidebarContext = value;
                },
            },
        },
    );
    context.after(provider.unmount);
    sidebarContext.setOpenMobile(true);
    assert.equal(sidebarContext.openMobile.value, true);
    const drawer = mount(
        'resources/js/components/ui/sidebar/Sidebar.vue',
        { class: 'association-navigation' },
        null,
        '/dashboard',
        {
            './utils': {
                useSidebar: () => sidebarContext,
                SIDEBAR_WIDTH_MOBILE: '18rem',
            },
        },
    );
    context.after(drawer.unmount);
    const content = drawer.all('SheetContent')[0];
    assert.equal(content.props.id, 'association-mobile-navigation');
    assert.equal(content.props.class.includes('[&>button]:hidden'), false);
    assert.ok(content.props.class.includes('association-navigation'));
    assert.match(drawer.text(), /Association navigation/);
    drawer.all('Sheet')[0].props['onUpdate:open'](false);
    assert.equal(sidebarContext.openMobile.value, false);
    let prevented = false;
    content.props.onCloseAutoFocus({
        preventDefault: () => {
            prevented = true;
        },
    });
    assert.equal(prevented, true);
    assert.equal(focusCount, 1);
});

void test('mobile Menu button exposes its expanded state and controls the drawer', (context) => {
    const openMobile = vue.ref(false);
    const ui = mount(
        'resources/js/components/ui/sidebar/SidebarTrigger.vue',
        {},
        null,
        '/dashboard',
        {
            './utils': {
                useSidebar: () => ({
                    isMobile: vue.ref(true),
                    state: vue.ref('expanded'),
                    openMobile,
                    toggleSidebar: () => {
                        openMobile.value = !openMobile.value;
                    },
                }),
            },
        },
    );
    context.after(ui.unmount);
    const button = ui.all('Button')[0];
    assert.match(ui.text(), /Menu/);
    assert.equal(button.props['aria-expanded'], false);
    assert.equal(
        button.props['aria-controls'],
        'association-mobile-navigation',
    );
    button.props.onClick();
    assert.equal(openMobile.value, true);
});

void test('property profiles reveal the relevant workspace and detail pages highlight their parent destination', (context) => {
    for (const officer of [false, true]) {
        const ui = setup(
            context,
            { ...member, canAccessOfficer: officer },
            '/properties/4/profile',
        );
        assert.equal(
            ui.all('Accordion')[0].props.modelValue,
            officer ? 'officer' : 'membership',
        );
    }
    const ui = setup(
        context,
        { ...member, canAccessOfficer: true },
        '/officer/charges/4',
    );
    const active = ui
        .all('Link')
        .filter((node) => node.props['aria-current'] === 'page');
    assert.equal(active.length, 1);
    assert.equal(active[0].props.href.url, '/officer/charges');
});

void test('collapsed navigation keeps role destinations and the selected link reachable', (context) => {
    const ui = setup(
        context,
        { ...member, canAccessOfficer: true },
        '/officer/charges/4',
        false,
        true,
    );
    assert.equal(ui.all('AccordionTrigger').length, 0);
    assert.ok(ui.all('DropdownMenuTrigger').length > 0);
    for (const path of [
        '/officer/announcements',
        '/officer/levy-settings',
        '/officer/charges',
    ])
        assert.ok(ui.destinations().includes(path));
    assert.equal(
        ui.all('Link').find((node) => node.props['aria-current'] === 'page')
            .props.href.url,
        '/officer/charges',
    );
});

void test('role sections can close and reopen while visits select the current workspace', async (context) => {
    const ui = setup(
        context,
        { ...member, canAccessOfficer: true, isSuperAdmin: true },
        '/officer/announcements',
    );
    const accordion = () => ui.all('Accordion')[0];
    assert.notEqual(accordion().props.collapsible, undefined);
    assert.equal(accordion().props.modelValue, 'officer');
    accordion().props['onUpdate:modelValue'](undefined);
    await vue.nextTick();
    assert.equal(accordion().props.modelValue, undefined);
    accordion().props['onUpdate:modelValue']('super-admin');
    await vue.nextTick();
    assert.equal(accordion().props.modelValue, 'super-admin');
    ui.page.url = '/officer/charges/4';
    await vue.nextTick();
    assert.equal(accordion().props.modelValue, 'officer');
    assert.equal(
        ui.all('Link').find((node) => node.props['aria-current'] === 'page')
            .props.href.url,
        '/officer/charges',
    );
});
