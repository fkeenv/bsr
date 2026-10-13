import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as vue from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { mount } from './mount-vue.mjs';
function setup(context, props = {}) {
    let outside;
    const ui = mount(
        'resources/js/components/DataTable.vue',
        {
            columns: [],
            data: [],
            action: '/records',
            searchables: ['name'],
            filters: ['status'],
            dateRanges: ['recorded'],
            filterOptions: { status: [{ value: 'live', label: 'Live' }] },
            values: {
                search: 'Ana',
                status: 'live',
                recorded_from: '2026-10-01',
                recorded_to: '2026-10-13',
            },
            ...props,
        },
        null,
        '',
        {
            '@vueuse/core': {
                useDebounceFn,
                onClickOutside: (_, callback) => {
                    outside = callback;
                },
            },
        },
    );
    context.after(ui.unmount);
    const button = (label) =>
        ui.all('Button').find((node) => node.props['aria-label'] === label);
    return { ...ui, button, outside: () => outside() };
}
void test('search expands inline and outside clicks hide it without clearing the applied query', async (context) => {
    const ui = setup(context);
    assert.equal(ui.all('Sheet').length, 0);
    assert.equal(ui.all('Input').length, 0);
    ui.button('Search table').props.onClick();
    await vue.nextTick();
    assert.equal(ui.all('Input').length, 1);
    assert.equal(ui.all('Input')[0].props['model-value'], 'Ana');
    assert.equal(ui.button('Search table').props['aria-expanded'], true);
    ui.all('Input')[0].props['onUpdate:modelValue']('  Ben  ');
    ui.all('form')[0].props.onSubmit({ preventDefault() {} });
    assert.deepEqual(ui.visits[0], [
        '/records',
        {
            search: 'Ben',
            status: 'live',
            recorded_from: '2026-10-01',
            recorded_to: '2026-10-13',
        },
        { preserveState: true, preserveScroll: true, replace: true },
    ]);
    ui.outside();
    await vue.nextTick();
    assert.equal(ui.all('Input').length, 0);
    assert.equal(ui.button('Search table').props['aria-expanded'], false);
    ui.button('Search table').props.onClick();
    await vue.nextTick();
    assert.equal(ui.all('Input')[0].props['model-value'], '  Ben  ');
    ui.all('form')[0].props.onKeydown({ key: 'Escape' });
    await vue.nextTick();
    assert.equal(ui.all('Input').length, 0);
});
void test('filter icon toggles inline controls and selections preserve search and date criteria', async (context) => {
    const ui = setup(context);
    assert.equal(ui.all('Select').length, 0);
    ui.button('Filter table').props.onClick();
    await vue.nextTick();
    assert.equal(ui.all('Select').length, 1);
    assert.equal(ui.all('DataTableDateRangeFilter').length, 1);
    ui.all('Select')[0].props['onUpdate:modelValue']('__all__');
    assert.deepEqual(ui.visits[0][1], {
        search: 'Ana',
        recorded_from: '2026-10-01',
        recorded_to: '2026-10-13',
    });
    ui.all('DataTableDateRangeFilter')[0].props.onChange(
        '2026-10-03',
        '2026-10-08',
    );
    assert.deepEqual(ui.visits[1][1], {
        search: 'Ana',
        status: 'live',
        recorded_from: '2026-10-03',
        recorded_to: '2026-10-08',
    });
    ui.button('Filter table').props.onClick();
    await vue.nextTick();
    assert.equal(ui.all('Select').length, 0);
    assert.equal(ui.visits.length, 2);
});
void test('Reset cancels pending search updates instead of restoring cleared filters', async (context) => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup(context);
    ui.button('Search table').props.onClick();
    await vue.nextTick();
    ui.all('Input')[0].props['onUpdate:modelValue']('Ben');
    assert.equal(ui.visits.length, 0);
    context.mock.timers.tick(300);
    await vue.nextTick();
    assert.equal(ui.visits[0][1].search, 'Ben');
    ui.all('Input')[0].props['onUpdate:modelValue']('Carl');
    ui.all('Button')
        .find((node) => ui.text(node).trim() === 'Reset')
        .props.onClick();
    context.mock.timers.tick(300);
    await vue.nextTick();
    assert.equal(ui.visits.length, 2);
    assert.deepEqual(ui.visits[1][1], {});
    assert.equal(ui.all('Input')[0].props['model-value'], '');
});
void test('controls only appear when the table supports search filters or date ranges', async (context) => {
    const search = setup(context, { filters: [], dateRanges: [], values: {} });
    assert.ok(search.button('Search table'));
    assert.equal(search.button('Filter table'), undefined);
    const dates = setup(context, { searchables: [], filters: [], values: {} });
    assert.equal(dates.button('Search table'), undefined);
    dates.button('Filter table').props.onClick();
    await vue.nextTick();
    assert.equal(dates.all('DataTableDateRangeFilter').length, 1);
    const plain = setup(context, {
        searchables: [],
        filters: [],
        dateRanges: [],
        values: {},
    });
    assert.equal(plain.all('Button').length, 0);
});

void test('opening search or filters closes the other and a second click collapses the active control', async (context) => {
    const ui = setup(context);
    const iconGroup = ui.button('Search table').parent;
    for (const [tool, searches, filters] of [
        ['Filter table', 0, 1],
        ['Search table', 1, 0],
        ['Filter table', 0, 1],
        ['Filter table', 0, 0],
        ['Search table', 1, 0],
        ['Search table', 0, 0],
    ]) {
        ui.button(tool).props.onClick();
        await vue.nextTick();
        assert.equal(ui.button('Search table').parent, iconGroup);
        assert.equal(ui.button('Filter table').parent, iconGroup);
        assert.equal(ui.all('Input', iconGroup).length, 0);
        assert.equal(ui.all('Select', iconGroup).length, 0);
        assert.equal(ui.all('Input').length, searches);
        assert.equal(ui.all('Select').length, filters);
        assert.equal(ui.all('DataTableDateRangeFilter').length, filters);
        assert.equal(
            ui.button('Search table').props['aria-expanded'],
            searches === 1,
        );
        assert.equal(
            ui.button('Filter table').props['aria-expanded'],
            filters === 1,
        );
    }
    assert.equal(ui.visits.length, 0);
    assert.equal(ui.values.values.search, 'Ana');
    assert.equal(ui.values.values.status, 'live');
});

void test('column menu marks visible columns and toggles actual table headers and cells', async (context) => {
    const tanstack = await import('@tanstack/vue-table');
    const features = tanstack.tableFeatures({
        columnVisibilityFeature: tanstack.columnVisibilityFeature,
    });
    const ui = mount(
        'resources/js/components/DataTable.vue',
        {
            columns: [
                { accessorKey: 'name', header: 'Name' },
                { accessorKey: 'email', header: 'Email' },
                { id: 'actions', header: 'Actions', enableHiding: false },
            ],
            data: [{ id: 1, name: 'Ana', email: 'ana@example.test' }],
            action: '/records',
            searchables: ['name'],
        },
        null,
        '',
        {
            '@tanstack/vue-table': tanstack,
            '@/components/data-table/features': { dataTableFeatures: features },
        },
    );
    context.after(ui.unmount);
    const email = () =>
        ui
            .all('DropdownMenuCheckboxItem')
            .find((node) => ui.text(node).trim() === 'email');
    assert.equal(ui.all('DropdownMenuCheckboxItem').length, 2);
    assert.equal(email().props['model-value'], true);
    assert.match(ui.text(ui.all('Table')[0]), /ana@example.test/);
    email().props['onUpdate:modelValue'](false);
    await vue.nextTick();
    assert.equal(email().props['model-value'], false);
    assert.doesNotMatch(ui.text(ui.all('Table')[0]), /Email|ana@example.test/);
    assert.match(ui.text(ui.all('Table')[0]), /Name|Ana/);
    email().props['onUpdate:modelValue'](true);
    await vue.nextTick();
    assert.equal(email().props['model-value'], true);
    assert.match(ui.text(ui.all('Table')[0]), /Email/);
    assert.match(ui.text(ui.all('Table')[0]), /ana@example.test/);
});
