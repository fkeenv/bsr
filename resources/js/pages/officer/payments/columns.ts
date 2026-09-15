import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import DataTableColumnHeader from '@/components/data-table/DataTableColumnHeader.vue';
import type { DataTableFeatures } from '@/components/data-table/features';
import { Badge } from '@/components/ui/badge';
import PaymentRowActions from '@/pages/officer/payments/PaymentRowActions.vue';
import type { Payment } from '@/types/payment';

const columnHelper = createColumnHelper<DataTableFeatures, Payment>();

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'rejected' || status === 'voided') {
        return 'destructive';
    }

    if (status === 'confirmed') {
        return 'default';
    }

    return 'secondary';
}

function methodLabel(method: string): string {
    if (method === 'gcash') {
        return 'GCash';
    }

    if (method === 'maya') {
        return 'Maya';
    }

    return method.charAt(0).toUpperCase() + method.slice(1);
}

function formatRecordedOn(value: string | null): string {
    if (!value) {
        return '—';
    }

    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return value;
    }

    return new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(Date.UTC(year, month - 1, day, 12)));
}

export const paymentColumns = columnHelper.columns([
    columnHelper.accessor('recorded_on', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Recorded' }),
        cell: ({ getValue }) => formatRecordedOn(getValue()),
        sortFn: 'alphanumeric',
    }),
    columnHelper.accessor('property_label', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Property' }),
        cell: ({ getValue }) => getValue() || '—',
        sortFn: 'text',
    }),
    columnHelper.accessor('amount', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Amount' }),
        cell: ({ getValue }) => `₱${getValue()}`,
        sortFn: 'alphanumeric',
    }),
    columnHelper.accessor('method', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Method' }),
        cell: ({ getValue }) => methodLabel(getValue()),
        sortFn: 'text',
    }),
    columnHelper.accessor('reference', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Reference' }),
        cell: ({ getValue }) => getValue() || '—',
        sortFn: 'text',
    }),
    columnHelper.accessor('declared_by_name', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Declared by' }),
        cell: ({ getValue }) => getValue() || 'Officer',
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
            h(PaymentRowActions, {
                payment: row.original,
            }),
    }),
]);
