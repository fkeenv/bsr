import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { DataTableFeatures } from '@/components/data-table/features';
import { show } from '@/routes/administrator/user-accounts';
import type { UserAccount } from '@/types/user-account';

export const columns: ColumnDef<DataTableFeatures, UserAccount, any>[] = [
    { accessorKey: 'name', header: 'Name' },
    { accessorKey: 'email', header: 'Email' },
    {
        accessorKey: 'mobile_number',
        header: 'Mobile number',
        cell: ({ row }) => row.original.mobile_number || '—',
    },
    {
        id: 'staff_roles',
        header: 'Staff roles',
        cell: ({ row }) =>
            [
                row.original.is_super_admin ? 'Super Admin' : '',
                ...row.original.staff_roles.map((name) =>
                    name.replaceAll('-', ' '),
                ),
            ]
                .filter(Boolean)
                .join(', ') || 'None',
    },
    {
        id: 'memberships',
        header: 'Live Property Memberships',
        cell: ({ row }) =>
            row.original.live_memberships
                .map(
                    (membership) =>
                        `${membership.property_label} · ${membership.role === 'owner' ? 'Owner' : 'Resident'}`,
                )
                .join('; ') || 'None',
    },
    {
        accessorKey: 'registered_at',
        header: 'Registered',
        cell: ({ row }) =>
            row.original.registered_at
                ? new Date(row.original.registered_at).toLocaleDateString(
                      'en-PH',
                      { timeZone: 'Asia/Manila' },
                  )
                : '—',
    },
    {
        id: 'actions',
        header: '',
        enableHiding: false,
        cell: ({ row }) =>
            h(Button, { asChild: true, variant: 'outline', size: 'sm' }, () =>
                h(Link, { href: show(row.original.id) }, () => 'Manage'),
            ),
    },
];
