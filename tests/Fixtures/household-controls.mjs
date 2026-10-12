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

void test('permission switches separate Read Write and Destructive actions and bulk changes stay within their group', async (context) => {
    const options = [
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
        {
            value: 'properties.delete',
            label: 'Delete Properties',
            description: 'Delete uncharged Properties',
            feature: 'Properties',
        },
    ];
    const ui = setup(
        context,
        'resources/js/pages/super-admin/role-permissions/RolePermissionEditor.vue',
        {
            role: {
                name: 'officer',
                label: 'Officer',
                grants: ['announcements.read'],
            },
            permissions: options,
        },
        [],
    );
    const permission = (value) =>
        ui
            .all('Switch')
            .find((control) => control.props.id === 'officer-' + value);
    const group = (label) =>
        ui
            .all('Switch')
            .find(
                (control) =>
                    control.props['aria-label'] ===
                    'All ' + label + ' permissions for Officer',
            );
    const enabled = () =>
        options
            .filter((option) => permission(option.value).props['model-value'])
            .map((option) => option.value);
    assert.deepEqual(
        ui.all('fieldset').map((node) => node.props['aria-label']),
        ['Read permissions', 'Write permissions', 'Destructive permissions'],
    );
    assert.deepEqual(
        ui.all('AccordionItem').map((node) => node.props.value),
        ['officer', 'read', 'write', 'destructive'],
    );
    assert.equal(group('Read').props['model-value'], false);
    assert.match(ui.text(ui.all('AccordionTrigger')[1]), /1 of 2 on/);
    group('Read').props['onUpdate:modelValue'](true);
    await vue.nextTick();
    assert.deepEqual(enabled(), ['announcements.read', 'properties.read']);
    assert.equal(group('Read').props['model-value'], true);
    assert.equal(group('Write').props['model-value'], false);
    group('Read').props['onUpdate:modelValue'](false);
    await vue.nextTick();
    assert.deepEqual(enabled(), []);
    permission('properties.create').props['onUpdate:modelValue'](true);
    await vue.nextTick();
    assert.deepEqual(enabled(), ['properties.create']);
    assert.equal(group('Write').props['model-value'], true);
    assert.equal(group('Read').props['model-value'], false);
    group('Destructive').props['onUpdate:modelValue'](true);
    await vue.nextTick();
    assert.deepEqual(enabled(), ['properties.create', 'properties.delete']);
    ui.all('Button')
        .find(
            (button) =>
                button.props['aria-label'] === 'Enable all Officer permissions',
        )
        .props.onClick();
    await vue.nextTick();
    assert.deepEqual(
        enabled(),
        options.map((option) => option.value),
    );
    assert.match(ui.text(), /4 of 4 permissions enabled/);
    ui.all('Button')
        .find(
            (button) =>
                button.props['aria-label'] ===
                'Disable all Officer permissions',
        )
        .props.onClick();
    await vue.nextTick();
    assert.deepEqual(enabled(), []);
    assert.equal(
        ui.all('Button').find((button) => button.props.type === 'submit').props
            .disabled,
        false,
    );
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

void test('Payment controls require both the action grant and the applicable Payment state', (context) => {
    for (const [status, grants, forms, dialog] of [
        ['pending', ['payments.read'], 0, false],
        ['pending', ['payments.confirm'], 1, true],
        ['pending', ['payments.reject'], 1, true],
        ['pending', ['payments.void'], 0, false],
        ['confirmed', ['payments.confirm'], 0, false],
        ['confirmed', ['payments.void'], 1, true],
        ['pending', ['payments.confirm', 'payments.reject'], 2, true],
        [
            'rejected',
            ['payments.confirm', 'payments.reject', 'payments.void'],
            0,
            false,
        ],
    ]) {
        const ui = setup(
            context,
            'resources/js/pages/officer/payments/PaymentRowActions.vue',
            {
                payment: {
                    id: 1,
                    status,
                    amount: '100.00',
                    method: 'cash',
                    property_label: 'Block 1 · Lot 1',
                },
            },
            grants,
        );
        assert.equal(ui.all('Form').length, forms);
        assert.equal(ui.all('Dialog').length, dialog ? 1 : 0);
        if (!grants.includes('payments.reject'))
            assert.equal(
                ui
                    .all('Input')
                    .some((input) => input.props.name === 'rejection_reason'),
                false,
            );
    }
});

void test('financial list readers see no create generate or edit controls without those grants', (context) => {
    for (const { directory, file, props, read, mutate } of [
        {
            directory: 'payments',
            file: 'Index',
            props: {
                payments: [],
                table: {
                    searchables: [],
                    filters: [],
                    dateRanges: [],
                    filterOptions: {},
                    values: {},
                },
            },
            read: 'payments.read',
            mutate: 'payments.create',
        },
        {
            directory: 'charges',
            file: 'Index',
            props: { charges: [] },
            read: 'charges.read',
            mutate: 'charges.generate',
        },
        {
            directory: 'fee-types',
            file: 'Index',
            props: { feeTypes: [] },
            read: 'fee-types.read',
            mutate: 'fee-types.create',
        },
        {
            directory: 'charges',
            file: 'ChargeRowActions',
            props: { charge: { id: 8 } },
            read: 'charges.read',
            mutate: 'charges.edit',
        },
        {
            directory: 'fee-types',
            file: 'FeeTypeRowActions',
            props: { feeType: { id: 8 } },
            read: 'fee-types.read',
            mutate: 'fee-types.edit',
        },
    ]) {
        const reader = setup(
            context,
            `resources/js/pages/officer/${directory}/${file}.vue`,
            props,
            [read],
        );
        assert.equal(reader.all('Link').length, 0);
        const writer = setup(
            context,
            `resources/js/pages/officer/${directory}/${file}.vue`,
            props,
            [mutate],
        );
        assert.equal(writer.all('Link').length, 1);
    }
});

void test('settings readers have disabled fields and no Save or channel mutation controls', (context) => {
    for (const canEdit of [false, true]) {
        for (const { directory, props } of [
            { directory: 'levy-settings', props: { levyDayOfMonth: 15 } },
            {
                directory: 'bill-settings',
                props: {
                    letterheadName: 'BSR',
                    letterheadShortName: 'BSR',
                    letterheadAddressLines: ['Basak'],
                    letterheadContact: 'Office',
                    letterheadTreasurer: 'Treasurer',
                    paymentChannels: [{ method: 'Cash', detail: 'Office' }],
                },
            },
        ]) {
            const ui = setup(
                context,
                `resources/js/pages/officer/${directory}/Edit.vue`,
                { ...props, canEdit },
                [],
            );
            assert.equal(ui.all('fieldset')[0].props.disabled, !canEdit);
            assert.equal(
                ui
                    .all('Button')
                    .filter((button) => button.props.type === 'submit').length,
                canEdit ? 1 : 0,
            );
            if (!canEdit) assert.doesNotMatch(ui.text(), /Add channel|Remove/);
        }
    }
});

void test('single and batch print buttons follow distinct grants on the unpaid screens', (context) => {
    const statement = {
        property: { id: 8, label: 'Block 1 · Lot 1' },
        outstanding_balance: '100.00',
        remaining_opening_balance: '0.00',
        prepaid_balance: '0.00',
        pending_declarations: [],
        periods: [],
        selected_period: {
            charge_id: 1,
            year: 2026,
            month: 10,
            label: 'October 2026',
            status: 'unpaid',
            remaining: '100.00',
            charge_total: '100.00',
            lines: [],
            payments: [],
        },
    };
    const roster = {
        roster_count: 1,
        unpaid_count: 1,
        filters_active: false,
        rows: [],
        values: {},
        filter_options: { blocks: [], owes_for: [] },
        empty_state: 'no_matches',
        selected: null,
        this_billing_period: { year: 2026, month: 10, label: 'October 2026' },
    };
    for (const [grants, single, batch] of [
        [['unpaid.read'], 0, 0],
        [['printed-bills.single'], 1, 0],
        [['printed-bills.batch'], 0, 1],
    ]) {
        const panel = setup(
            context,
            'resources/js/pages/officer/unpaid/PropertyStatementPanel.vue',
            { statement },
            grants,
        );
        const list = setup(
            context,
            'resources/js/pages/officer/unpaid/Index.vue',
            roster,
            grants,
        );
        assert.equal(panel.all('a').length, single);
        assert.equal(list.all('a').length, batch);
    }
});
