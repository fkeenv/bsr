import { h } from 'vue';
import type { ColumnDef } from '@tanstack/vue-table';
import type { DataTableFeatures } from '@/components/data-table/features';
import { Badge } from '@/components/ui/badge';
import AccessHistoryRowActions from './AccessHistoryRowActions.vue';
import type { AccessHistoryEntry } from '@/types/access-history';
export function columns(
    permissionLabels: Record<string, string>,
    actionLabels: Record<string, string>,
): ColumnDef<DataTableFeatures, AccessHistoryEntry, any>[] {
    return [
        {
            accessorKey: 'occurred_at',
            header: 'When',
            cell: ({ row }) =>
                new Date(row.original.occurred_at).toLocaleString('en-PH', {
                    timeZone: 'Asia/Manila',
                }),
        },
        {
            accessorKey: 'action',
            header: 'Change',
            cell: ({ row }) =>
                actionLabels[row.original.action] || row.original.action,
        },
        {
            accessorKey: 'actor_name',
            header: 'Actor / origin',
            cell: ({ row }) =>
                h('div', { class: 'space-y-1' }, [
                    h('span', row.original.actor_name),
                    ...(row.original.origin === 'automatic'
                        ? [
                              h(
                                  Badge,
                                  { variant: 'secondary' },
                                  () => 'Automatic',
                              ),
                          ]
                        : []),
                ]),
        },
        {
            id: 'affected',
            header: 'Affected account / role',
            cell: ({ row }) =>
                row.original.account_name
                    ? `${row.original.account_name} · ${row.original.account_email}`
                    : row.original.previous_role_name &&
                        row.original.new_role_name &&
                        row.original.previous_role_name !==
                            row.original.new_role_name
                      ? `${row.original.previous_role_name} → ${row.original.new_role_name}`
                      : row.original.role_name || '—',
        },
        {
            id: 'details',
            header: '',
            enableHiding: false,
            cell: ({ row }) =>
                h(AccessHistoryRowActions, {
                    entry: row.original,
                    permissionLabels,
                }),
        },
    ];
}
