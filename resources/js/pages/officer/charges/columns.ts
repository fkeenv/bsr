import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import DataTableColumnHeader from '@/components/data-table/DataTableColumnHeader.vue';
import type { DataTableFeatures } from '@/components/data-table/features';
import { Badge } from '@/components/ui/badge';
import ChargeRowActions from '@/pages/officer/charges/ChargeRowActions.vue';
import type { Charge } from '@/types/charge';

const columnHelper = createColumnHelper<DataTableFeatures, Charge>();

export const chargeColumns = columnHelper.columns([
    columnHelper.accessor('property_label', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Property' }),
        sortFn: 'text',
    }),
    columnHelper.accessor('period_label', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Billing Period' }),
        sortFn: 'alphanumeric',
    }),
    columnHelper.accessor('is_frozen', {
        id: 'status',
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Status' }),
        cell: ({ getValue }) =>
            h(Badge, { variant: getValue() ? 'secondary' : 'default' }, () =>
                getValue() ? 'Frozen' : 'Editable',
            ),
        sortFn: 'basic',
    }),
    columnHelper.display({
        id: 'actions',
        enableHiding: false,
        enableSorting: false,
        header: () => h('div', { class: 'text-right' }, 'Actions'),
        cell: ({ row }) => h(ChargeRowActions, { charge: row.original }),
    }),
]);
