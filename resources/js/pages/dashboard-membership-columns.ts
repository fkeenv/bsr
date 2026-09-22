import { Link } from '@inertiajs/vue3';
import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import DataTableColumnHeader from '@/components/data-table/DataTableColumnHeader.vue';
import type { DataTableFeatures } from '@/components/data-table/features';
import { Badge } from '@/components/ui/badge';
import DashboardMembershipRowActions from '@/pages/DashboardMembershipRowActions.vue';
import { edit as editPropertyProfile } from '@/routes/property-profile';
import { show as statementOfAccount } from '@/routes/statement-of-account';
import type { Membership } from '@/types/membership';

const columnHelper = createColumnHelper<DataTableFeatures, Membership>();

export const dashboardMembershipColumns = columnHelper.columns([
    columnHelper.accessor('property_label', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Property' }),
        cell: ({ row, getValue }) => {
            const label = getValue() || '—';

            return h('div', { class: 'flex flex-col items-start gap-1' }, [
                h(
                    Link,
                    {
                        href: statementOfAccount(row.original.property_id),
                        class: 'text-primary font-medium underline-offset-4 hover:underline',
                    },
                    () => label,
                ),
                h(
                    Link,
                    {
                        href: editPropertyProfile(row.original.property_id),
                        class: 'text-muted-foreground text-xs underline-offset-4 hover:text-foreground hover:underline',
                    },
                    () => 'Shared profile',
                ),
            ]);
        },
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
            h(DashboardMembershipRowActions, { membership: row.original }),
    }),
]);
