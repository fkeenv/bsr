import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import DataTableColumnHeader from '@/components/data-table/DataTableColumnHeader.vue';
import type { DataTableFeatures } from '@/components/data-table/features';
import SuspendRowActions from '@/pages/officer/suspends/SuspendRowActions.vue';
import type { Suspend } from '@/types/suspend';

const columnHelper = createColumnHelper<DataTableFeatures, Suspend>();

function periodLabel(year: number, month: number): string {
    return `${year}-${String(month).padStart(2, '0')}`;
}

export const suspendColumns = columnHelper.columns([
    columnHelper.accessor('property_label', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Property' }),
        sortFn: 'text',
    }),
    columnHelper.accessor('fee_type_name', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Fee Type' }),
        sortFn: 'text',
    }),
    columnHelper.accessor(
        (row) => periodLabel(row.starts_year, row.starts_month),
        {
            id: 'starts',
            header: ({ column }) =>
                h(DataTableColumnHeader, { column, title: 'Starts' }),
            sortFn: 'alphanumeric',
        },
    ),
    columnHelper.accessor(
        (row) =>
            row.ends_year && row.ends_month
                ? periodLabel(row.ends_year, row.ends_month)
                : 'Standing',
        {
            id: 'ends',
            header: ({ column }) =>
                h(DataTableColumnHeader, { column, title: 'Ends' }),
            sortFn: 'text',
        },
    ),
    columnHelper.display({
        id: 'actions',
        enableHiding: false,
        enableSorting: false,
        header: () => h('div', { class: 'text-right' }, 'Actions'),
        cell: ({ row }) => h(SuspendRowActions, { suspend: row.original }),
    }),
]);
