import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import DataTableColumnHeader from '@/components/data-table/DataTableColumnHeader.vue';
import type { DataTableFeatures } from '@/components/data-table/features';
import { Badge } from '@/components/ui/badge';
import InvitationRowActions from '@/pages/officer/property-invitations/InvitationRowActions.vue';
import type {
    PropertyInvitation,
    PropertyInvitationStatus,
} from '@/types/property-invitation';

const columnHelper = createColumnHelper<
    DataTableFeatures,
    PropertyInvitation
>();

function statusVariant(
    status: PropertyInvitationStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'revoked') {
        return 'destructive';
    }

    if (status === 'consumed') {
        return 'outline';
    }

    if (status === 'expired') {
        return 'secondary';
    }

    return 'default';
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

export const propertyInvitationColumns = columnHelper.columns([
    columnHelper.accessor('property_label', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Property' }),
        sortFn: 'text',
    }),
    columnHelper.accessor('role', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Role' }),
        cell: ({ getValue }) => h('span', { class: 'capitalize' }, getValue()),
        sortFn: 'text',
    }),
    columnHelper.accessor('creator_name', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Created by' }),
        sortFn: 'text',
    }),
    columnHelper.accessor('created_at', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Created' }),
        cell: ({ getValue }) => formatDateTime(getValue()),
        sortFn: 'text',
    }),
    columnHelper.accessor('expires_at', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Expires' }),
        cell: ({ getValue }) => formatDateTime(getValue()),
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
            h(InvitationRowActions, { invitation: row.original }),
    }),
]);
