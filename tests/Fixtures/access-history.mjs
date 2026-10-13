import assert from 'node:assert/strict';
import { test } from 'node:test';
import { mount } from './mount-vue.mjs';
const entry = {
    id: 'role-1',
    source: 'role',
    action: 'delete',
    occurred_at: '2026-10-13T12:00:00+00:00',
    origin: 'user',
    actor_name: 'Root Reviewer',
    account_name: null,
    account_email: null,
    role_name: 'finance-team',
    previous_role_name: 'finance-team',
    new_role_name: null,
    previous_values: ['payments.read', 'legacy.permission'],
    new_values: [],
    reason: null,
};
void test('history details show historical before and after values without editing controls', (context) => {
    const ui = mount(
        'resources/js/pages/super-admin/access-history/AccessHistoryRowActions.vue',
        {
            entry,
            permissionLabels: { 'payments.read': 'Payments · View payments' },
        },
    );
    context.after(ui.unmount);
    assert.match(ui.text(), /Before/);
    assert.match(ui.text(), /After/);
    assert.match(ui.text(), /finance-team/);
    assert.match(ui.text(), /Not present/);
    assert.match(ui.text(), /Payments · View payments/);
    assert.match(ui.text(), /legacy.permission/);
    assert.match(ui.text(), /Root Reviewer/);
    assert.match(ui.text(), /read-only/);
    assert.equal(ui.all('form').length, 0);
    assert.equal(ui.all('Input').length, 0);
    assert.deepEqual(
        ui.all('Button').map((node) => ui.text(node).trim()),
        ['View'],
    );
});
void test('automatic removal details explain why staff access was lost and retain the actor', (context) => {
    const ui = mount(
        'resources/js/pages/super-admin/access-history/AccessHistoryRowActions.vue',
        {
            entry: {
                ...entry,
                source: 'assignment',
                origin: 'automatic',
                action: 'automatic-removal',
                account_name: 'Ana',
                account_email: 'ana@example.test',
                previous_values: ['officer', 'finance-team'],
                reason: 'last-owner-membership-changed-to-resident',
            },
            permissionLabels: {},
        },
    );
    context.after(ui.unmount);
    assert.match(ui.text(), /Automatic removal/);
    assert.match(ui.text(), /Root Reviewer/);
    assert.match(ui.text(), /Ana/);
    assert.match(ui.text(), /last live Owner Membership changed to Resident/);
    assert.match(ui.text(), /finance team/);
    assert.equal(ui.all('form').length, 0);
});
void test('history index uses shared inline filters date ranges and keeps criteria when paging', (context) => {
    const ui = mount(
        'resources/js/pages/super-admin/access-history/Index.vue',
        {
            entries: [],
            pagination: { current_page: 1, last_page: 2, total: 26 },
            values: {
                actor: '3',
                action: 'permissions',
                occurred_from: '2026-10-13',
            },
            filter_options: { actor: [], account: [], role: [], action: [] },
            permission_labels: {},
        },
    );
    context.after(ui.unmount);
    assert.deepEqual(ui.all('DataTable')[0].props.filters, [
        'actor',
        'account',
        'role',
        'action',
    ]);
    assert.deepEqual(ui.all('DataTable')[0].props['date-ranges'], ['occurred']);
    assert.equal(ui.all('Input').length, 0);
    assert.equal(ui.all('form').length, 0);
    assert.equal(ui.all('Link').length, 1);
    assert.equal(
        ui.all('Link')[0].props.href.url,
        '/super-admin/access-history?actor=3&action=permissions&occurred_from=2026-10-13&page=2',
    );
});
