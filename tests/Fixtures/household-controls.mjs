import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { useForm, useHttp } from '@inertiajs/vue3';
import { mount } from './mount-vue.mjs';

const Form = vue.defineComponent({
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
function setup(context, file, props, grants, capabilities = {}, userId = 1) {
    const page = vue.reactive({
        props: {
            auth: {
                user: { id: userId },
                capabilities,
                staffPermissions: [...grants],
            },
        },
    });
    const ui = mount(file, props, null, '', {
        '@inertiajs/vue3': { usePage: () => page, useForm, useHttp, Form },
    });
    context.after(ui.unmount);
    return { ...ui, page };
}

const property = {
    id: 8,
    block: '2',
    lot: '4',
    is_active: true,
    has_been_charged: false,
};
void test('Property readers have no mutations; a deactivate grant exposes only its action', async (context) => {
    const ui = setup(
        context,
        'resources/js/pages/officer/properties/PropertyRowActions.vue',
        { property },
        ['properties.read'],
    );
    assert.equal(ui.all('Dialog').length, 0);
    ui.page.props.auth.staffPermissions = ['properties.deactivate'];
    await vue.nextTick();
    assert.equal(ui.all('Form').length, 1);
    assert.match(ui.text(), /Deactivate/);
    assert.doesNotMatch(ui.text(), /Delete|Shared profile/);
    assert.equal(ui.all('Link').length, 0);
    ui.page.props.auth.staffPermissions = ['properties.profiles.read'];
    await vue.nextTick();
    assert.equal(ui.all('Form').length, 0);
    assert.equal(ui.all('Link')[0].props.href.url, '/properties/8/profile');
});

void test('Membership role controls follow grants and prevent Administrator self management', (context) => {
    for (const [userId, capabilities, grants, expectedRoles, expectedEnds] of [
        [1, {}, ['memberships.read'], 0, 0],
        [1, {}, ['memberships.change-role'], 1, 0],
        [
            2,
            { canAccessAdministrator: true },
            ['memberships.change-role', 'memberships.end'],
            0,
            1,
        ],
        [2, { isSuperAdmin: true }, ['memberships.change-role'], 1, 0],
    ]) {
        const ui = setup(
            context,
            'resources/js/pages/officer/memberships/MembershipRowActions.vue',
            {
                membership: {
                    id: 9,
                    user_id: 2,
                    role: 'owner',
                    user_name: 'Ana',
                    property_label: 'Block 1 · Lot 1',
                },
            },
            grants,
            capabilities,
            userId,
        );
        assert.equal(ui.all('Select').length, expectedRoles);
        assert.equal(
            ui.all('Input').filter((node) => node.props.name === 'end_reason')
                .length,
            expectedEnds,
        );
        assert.equal(ui.all('Form').length, expectedRoles + expectedEnds);
        if (expectedRoles) {
            assert.deepEqual(
                ui.all('SelectItem').map((item) => item.props.value),
                ['owner', 'resident'],
            );
        }
    }
});

void test('Invitation reading does not reveal sharing or revoke controls and each mutation is independent', (context) => {
    const invitation = {
        id: 10,
        property_label: 'Block 1 · Lot 1',
        role: 'resident',
        can_share: true,
        can_revoke: true,
    };
    for (const [grants, expectedCopies, expectedRevoke] of [
        [['invitations.read'], 0, 0],
        [['invitations.credentials'], 2, 0],
        [['invitations.revoke'], 0, 1],
    ]) {
        const ui = setup(
            context,
            'resources/js/pages/officer/property-invitations/InvitationRowActions.vue',
            { invitation },
            grants,
        );
        assert.equal(
            ui.all('Button').filter((node) => /Copy/.test(ui.text(node)))
                .length,
            expectedCopies,
        );
        assert.equal(ui.all('Form').length, expectedRevoke);
    }
});

void test('Invitation creation and listing remain independent on their shared page', (context) => {
    for (const [grants, create, list] of [
        [['invitations.read'], 0, 1],
        [['invitations.create'], 1, 0],
    ]) {
        const ui = setup(
            context,
            'resources/js/pages/officer/property-invitations/Index.vue',
            {
                properties: [{ id: 8, label: 'Block 2 · Lot 4' }],
                invitations: [],
                table: { dateRanges: [], values: {} },
            },
            grants,
        );
        assert.equal(ui.all('form').length, create);
        assert.equal(ui.all('DataTable').length, list);
    }
});

void test('Suspend reading does not imply edit controls', (context) => {
    for (const [grants, expected] of [
        [['suspends.read'], 0],
        [['suspends.edit'], 1],
    ]) {
        const ui = setup(
            context,
            'resources/js/pages/officer/suspends/SuspendRowActions.vue',
            { suspend: { id: 7 } },
            grants,
        );
        assert.equal(ui.all('Link').length, expected);
    }
});

void test('the role editor groups distinct feature grants without adding related permissions', async (context) => {
    const ui = setup(
        context,
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
                    label: 'Read Announcements',
                    description: 'Read notices',
                    feature: 'Announcements',
                },
                {
                    value: 'properties.create',
                    label: 'Create Properties',
                    description: 'Add Properties',
                    feature: 'Properties',
                },
                {
                    value: 'properties.read',
                    label: 'List Properties',
                    description: 'Read the roster',
                    feature: 'Properties',
                },
            ],
        },
        [],
    );
    assert.deepEqual(
        ui.all('fieldset').map((node) => node.props['aria-label']),
        ['Announcements permissions', 'Properties permissions'],
    );
    assert.equal(ui.all('Accordion')[0].props.type, 'multiple');
    assert.deepEqual(
        ui.all('AccordionItem').map((node) => node.props.value),
        ['officer', 'Announcements', 'Properties'],
    );
    assert.match(ui.text(ui.all('AccordionTrigger')[2]), /0 of 2 enabled/);
    ui.all('Checkbox')[1].props['onUpdate:modelValue'](true);
    await vue.nextTick();
    assert.match(ui.text(ui.all('AccordionTrigger')[2]), /1 of 2 enabled/);
    assert.equal(ui.all('Checkbox')[1].props['model-value'], true);
    assert.equal(ui.all('Checkbox')[2].props['model-value'], false);
    assert.match(ui.text(), /2 of 3/);
});

void test('read-only Property Profiles cannot submit and Member editing still shows its save control', (context) => {
    for (const editable of [false, true]) {
        const ui = setup(
            context,
            'resources/js/pages/property-profile/Edit.vue',
            {
                profile: {
                    property_id: 8,
                    property_label: 'Block 2 · Lot 4',
                    saved_at: null,
                    household_members: [],
                    emergency_contacts: [],
                    vehicles: [],
                },
                canAccessOfficerProperties: false,
                canEditProfile: editable,
            },
            [],
        );
        assert.equal(ui.all('fieldset')[0].props.disabled, !editable);
        assert.equal(
            ui.all('Button').filter((node) => node.props.type === 'submit')
                .length,
            editable ? 1 : 0,
        );
        if (!editable)
            ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    }
});
