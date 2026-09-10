import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import DataTableColumnHeader from '@/components/data-table/DataTableColumnHeader.vue';
import type { DataTableFeatures } from '@/components/data-table/features';
import { Badge } from '@/components/ui/badge';
import MembershipRowActions from '@/pages/officer/memberships/MembershipRowActions.vue';
import type { Membership } from '@/types/membership';

const columnHelper = createColumnHelper<DataTableFeatures, Membership>();

export const membershipColumns = columnHelper.columns([
    columnHelper.accessor('user_name', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Member' }),
        cell: ({ getValue }) => getValue() || '—',
        sortFn: 'text',
    }),
    columnHelper.accessor('property_label', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Property' }),
        cell: ({ getValue }) => getValue() || '—',
        sortFn: 'text',
    }),
    columnHelper.accessor('role', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Role' }),
        cell: ({ getValue }) =>
            h(Badge, { variant: 'secondary', class: 'capitalize' }, () =>
                getValue(),
            ),
        sortFn: 'text',
    }),
    columnHelper.display({
        id: 'actions',
        enableHiding: false,
        enableSorting: false,
        header: () => h('div', { class: 'text-right' }, 'Actions'),
        cell: ({ row }) =>
            h(MembershipRowActions, { membership: row.original }),
    }),
]);
