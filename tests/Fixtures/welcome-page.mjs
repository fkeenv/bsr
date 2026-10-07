import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { mount } from './mount-vue.mjs';

function setup(context, canRegister, signedIn = false) {
    const page = { props: { auth: { user: signedIn ? { id: 7 } : null } } };
    const Link = vue.defineComponent({
        inheritAttrs: false,
        setup(_, { attrs, slots }) {
            return () => vue.h('a', attrs, slots.default?.());
        },
    });
    const Head = vue.defineComponent({ setup: () => () => null });
    const ui = mount(
        'resources/js/pages/Welcome.vue',
        { canRegister },
        null,
        '/',
        { '@inertiajs/vue3': { Link, Head, usePage: () => page } },
    );
    context.after(() => ui.unmount());
    return ui;
}

for (const canRegister of [true, false]) {
    void test(`guests see login and ${canRegister ? 'available' : 'no'} registration links`, (context) => {
        const ui = setup(context, canRegister);
        const destinations = ui.all('a').map((node) => node.props.href?.url);

        assert.ok(destinations.includes('/login'));
        assert.equal(destinations.includes('/register'), canRegister);
        assert.equal(destinations.includes('/dashboard'), false);
        assert.match(ui.text(), /Blessed Sacrament Residences/);
        assert.match(ui.text(), /Homeowners Association/);
        assert.match(ui.text(), /Property Invitation/);
        assert.match(ui.text(), /Statement of Account/);
        assert.match(ui.text(), /Property Profile/);
        assert.equal(ui.all('h1').length, 1);
    });
}

void test('signed-in accounts can open their dashboard without guest account prompts', (context) => {
    const ui = setup(context, true, true);
    const destinations = ui.all('a').map((node) => node.props.href?.url);

    assert.ok(destinations.includes('/dashboard'));
    assert.equal(destinations.includes('/login'), false);
    assert.equal(destinations.includes('/register'), false);
});

void test('community navigation targets visible sections without exposing restricted feeds', (context) => {
    const ui = setup(context, false);
    const links = ui.all('a');
    const headings = ui.all('h2').map((node) => ui.text(node).trim());
    const ids = [...ui.all('section'), ...ui.all('main')].map(
        (node) => node.props.id,
    );

    for (const link of links.filter(
        (node) =>
            typeof node.props.href === 'string' &&
            node.props.href.startsWith('#'),
    )) {
        assert.ok(ids.includes(link.props.href.slice(1)));
    }
    assert.ok(headings.includes('Our Community'));
    assert.ok(headings.includes('How it works'));
    assert.ok(headings.includes('Contacts'));
    assert.match(ui.text(), /Cagudoy, Basak/);
    assert.match(ui.text(), /Lapu-Lapu City, Cebu, Philippines/);
    assert.match(ui.text(), /To be announced/);
    assert.ok(links.some((node) => node.props.href === '#contacts'));
    assert.equal(
        links.some((node) => /^mailto:|^tel:/.test(node.props.href ?? '')),
        false,
    );
    assert.match(ui.text(), /An account alone does not create a Membership/);
    assert.match(ui.text(), /Terms of Service and Privacy Policy/);
    assert.equal(
        links.some((node) => node.props.href?.url === '/announcements'),
        false,
    );
    assert.equal(
        links.some((node) => node.props.href === 'https://laravel.com/docs'),
        false,
    );
});
