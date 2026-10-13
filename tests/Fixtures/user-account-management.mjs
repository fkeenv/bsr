import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { mount } from './mount-vue.mjs';
const account = {
    id: 12,
    name: 'Ana Owner',
    email: 'ana@example.test',
    has_owner_membership: true,
    is_super_admin: false,
    staff_roles: ['officer', 'treasurer'],
};
const membership = { id: 9, role: 'owner', property_label: 'Block 1 · Lot 2' };
function setup(context, file, props) {
    const requests = [];
    for (const method of ['post', 'put'])
        context.mock.method(router, method, (url, data, callbacks) => {
            requests.push({ method, url, data, callbacks });
            callbacks.onStart?.({});
        });
    context.mock.method(router, 'delete', (url, callbacks) => {
        requests.push({ method: 'delete', url, callbacks });
        callbacks.onStart?.({});
    });
    const ui = mount(
        `resources/js/pages/administrator/user-accounts/${file}.vue`,
        props,
        null,
        '',
        { '@inertiajs/vue3': { useForm } },
    );
    context.after(ui.unmount);
    return { ...ui, requests };
}
void test('staff assignment dialog respects management and ownership and confirms exact role and account', async (context) => {
    const ui = setup(context, 'StaffAssignmentDialog', {
        account,
        role: {
            name: 'treasurer',
            label: 'Treasurer',
            assigned: false,
            can_manage: true,
        },
    });
    assert.match(ui.text(), /Assign Treasurer/);
    assert.match(ui.text(), /Ana Owner/);
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    await vue.nextTick();
    assert.equal(
        ui.requests[0].url,
        '/administrator/user-accounts/12/staff-roles/treasurer',
    );
    assert.equal(ui.requests[0].method, 'post');
    assert.equal(ui.all('Button').at(-1).props.disabled, true);
    ui.requests[0].callbacks.onError({
        user_id: 'Ownership was lost. Reappointment requires ownership.',
    });
    ui.requests[0].callbacks.onFinish({});
    await vue.nextTick();
    assert.match(ui.all('InputError')[0].props.message, /Ownership was lost/);
    ui.values.account = { ...account, has_owner_membership: false };
    await vue.nextTick();
    assert.equal(ui.all('Button')[0].props.disabled, true);
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    assert.equal(ui.requests.length, 1);
    ui.values.role = {
        name: 'treasurer',
        label: 'Treasurer',
        assigned: true,
        can_manage: true,
    };
    await vue.nextTick();
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    assert.equal(ui.requests[1].method, 'delete');
    ui.values.role = { ...ui.values.role, can_manage: false };
    await vue.nextTick();
    assert.equal(ui.all('Dialog').length, 0);
});
void test('membership dialog identifies the Property and requires explicit loss confirmation before submitting', async (context) => {
    const ui = setup(context, 'AccountMembershipDialog', {
        account,
        membership,
        canEnd: true,
        canChangeRole: true,
        losesStaffAccess: true,
    });
    ui.all('Dialog')[0].props['onUpdate:open'](true);
    await vue.nextTick();
    assert.match(ui.text(), /Ana Owner/);
    assert.match(ui.text(), /Block 1 · Lot 2/);
    ui.all('Select')[1].props['onUpdate:modelValue']('resident');
    await vue.nextTick();
    assert.match(ui.text(), /All staff access will be removed/);
    assert.match(ui.text(), /officer, treasurer/);
    assert.equal(ui.all('Button').at(-1).props.disabled, true);
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    assert.equal(ui.requests.length, 0);
    ui.all('Checkbox')[0].props['onUpdate:modelValue'](true);
    await vue.nextTick();
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    assert.equal(
        ui.requests[0].url,
        '/administrator/user-accounts/12/memberships/9/role',
    );
    assert.equal(ui.requests[0].data.confirm_staff_access_loss, true);
    assert.equal(ui.requests[0].data.role, 'resident');
    ui.requests[0].callbacks.onFinish({});
    await ui.requests[0].callbacks.onSuccess({});
    await vue.nextTick();
    assert.equal(ui.all('Dialog')[0].props.open, false);
    ui.all('Dialog')[0].props['onUpdate:open'](true);
    await vue.nextTick();
    ui.all('Select')[0].props['onUpdate:modelValue']('end');
    await vue.nextTick();
    assert.equal(ui.all('Checkbox')[0].props.modelValue, false);
    ui.all('Textarea')[0].props['onUpdate:modelValue']('Sold Property');
    ui.all('Checkbox')[0].props['onUpdate:modelValue'](true);
    await vue.nextTick();
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    assert.equal(
        ui.requests[1].url,
        '/administrator/user-accounts/12/memberships/9/end',
    );
    assert.equal(ui.requests[1].data.end_reason, 'Sold Property');
});
void test('membership dialog does not imply mutation permissions and shows only allowed operation', async (context) => {
    const ui = setup(context, 'AccountMembershipDialog', {
        account,
        membership,
        canEnd: false,
        canChangeRole: false,
        losesStaffAccess: false,
    });
    assert.equal(ui.all('Dialog').length, 0);
    ui.values.canEnd = true;
    await vue.nextTick();
    ui.all('Dialog')[0].props['onUpdate:open'](true);
    await vue.nextTick();
    assert.equal(ui.all('Select').length, 0);
    assert.equal(ui.all('Textarea').length, 1);
    assert.equal(ui.all('Checkbox').length, 0);
});
