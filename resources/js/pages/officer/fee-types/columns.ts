import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import DataTableColumnHeader from '@/components/data-table/DataTableColumnHeader.vue';
import type { DataTableFeatures } from '@/components/data-table/features';
import { Badge } from '@/components/ui/badge';
import FeeTypeRowActions from '@/pages/officer/fee-types/FeeTypeRowActions.vue';
import type { FeeType } from '@/types/fee-type';

const columnHelper = createColumnHelper<DataTableFeatures, FeeType>();

export const feeTypeColumns = columnHelper.columns([
    columnHelper.accessor('name', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Name' }),
        sortFn: 'text',
    }),
    columnHelper.accessor('amount', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Amount' }),
        cell: ({ getValue }) => `₱${getValue()}`,
        sortFn: 'alphanumeric',
    }),
    columnHelper.accessor('is_retired', {
        id: 'status',
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Status' }),
        cell: ({ getValue }) =>
            h(Badge, { variant: getValue() ? 'secondary' : 'default' }, () =>
                getValue() ? 'Retired' : 'Active',
            ),
        sortFn: 'basic',
    }),
    columnHelper.display({
        id: 'actions',
        enableHiding: false,
        enableSorting: false,
        header: () => h('div', { class: 'text-right' }, 'Actions'),
        cell: ({ row }) => h(FeeTypeRowActions, { feeType: row.original }),
    }),
]);
