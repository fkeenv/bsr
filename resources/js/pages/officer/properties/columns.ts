import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import DataTableColumnHeader from '@/components/data-table/DataTableColumnHeader.vue';
import type { DataTableFeatures } from '@/components/data-table/features';
import { Badge } from '@/components/ui/badge';
import PropertyRowActions from '@/pages/officer/properties/PropertyRowActions.vue';
import type { Property } from '@/types/property';

const columnHelper = createColumnHelper<DataTableFeatures, Property>();

export const propertyColumns = columnHelper.columns([
    columnHelper.accessor('block', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Block' }),
        sortFn: 'alphanumeric',
    }),
    columnHelper.accessor('lot', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Lot' }),
        sortFn: 'alphanumeric',
    }),
    columnHelper.accessor('street_address', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Address' }),
        cell: ({ getValue }) => getValue() || '—',
        sortFn: 'text',
    }),
    columnHelper.accessor('recorded_owner_name', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Recorded owner' }),
        cell: ({ getValue }) => getValue() || '—',
        sortFn: 'text',
    }),
    columnHelper.accessor('opening_balance', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Opening Balance' }),
        cell: ({ getValue }) => `₱${getValue()}`,
        sortFn: 'alphanumeric',
    }),
    columnHelper.accessor('is_active', {
        id: 'status',
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Status' }),
        cell: ({ getValue }) =>
            h(Badge, { variant: getValue() ? 'default' : 'secondary' }, () =>
                getValue() ? 'Active' : 'Inactive',
            ),
        sortFn: 'basic',
    }),
    columnHelper.display({
        id: 'actions',
        enableHiding: false,
        enableSorting: false,
        header: () => h('div', { class: 'text-right' }, 'Actions'),
        cell: ({ row }) => h(PropertyRowActions, { property: row.original }),
    }),
]);
