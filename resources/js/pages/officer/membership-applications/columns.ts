import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import DataTableColumnHeader from '@/components/data-table/DataTableColumnHeader.vue';
import type { DataTableFeatures } from '@/components/data-table/features';
import { Badge } from '@/components/ui/badge';
import MembershipApplicationRowActions from '@/pages/officer/membership-applications/MembershipApplicationRowActions.vue';

export type MembershipApplicationRow = {
    id: number;
    status: string;
    property_label: string | null;
    applicant_name: string | null;
    applicant_email: string | null;
};

const columnHelper = createColumnHelper<
    DataTableFeatures,
    MembershipApplicationRow
>();

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'rejected') {
        return 'destructive';
    }

    if (status === 'approved') {
        return 'default';
    }

    return 'secondary';
}

export const membershipApplicationColumns = columnHelper.columns([
    columnHelper.accessor('applicant_name', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Applicant' }),
        cell: ({ getValue }) => getValue() || '—',
        sortFn: 'text',
    }),
    columnHelper.accessor('applicant_email', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Email' }),
        cell: ({ getValue }) => getValue() || '—',
        sortFn: 'text',
    }),
    columnHelper.accessor('property_label', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Property' }),
        cell: ({ getValue }) => getValue() || '—',
        sortFn: 'text',
    }),
    columnHelper.accessor('status', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Status' }),
        cell: ({ getValue }) => {
            const status = getValue();

            return h(
                Badge,
                { variant: statusVariant(status), class: 'capitalize' },
                () => status,
            );
        },
        sortFn: 'text',
    }),
    columnHelper.display({
        id: 'actions',
        enableHiding: false,
        enableSorting: false,
        header: () => h('div', { class: 'text-right' }, 'Actions'),
        cell: ({ row }) =>
            h(MembershipApplicationRowActions, {
                applicationId: row.original.id,
            }),
    }),
]);
