import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import DataTableColumnHeader from '@/components/data-table/DataTableColumnHeader.vue';
import type { DataTableFeatures } from '@/components/data-table/features';
import { Badge } from '@/components/ui/badge';
import AdministratorRowActions from '@/pages/super-admin/administrators/AdministratorRowActions.vue';
import type { PlatformRoleCandidate } from '@/types/platform-role-candidate';

const columnHelper = createColumnHelper<
    DataTableFeatures,
    PlatformRoleCandidate
>();

function roleBadges(candidate: PlatformRoleCandidate) {
    const badges = [];

    if (candidate.is_officer) {
        badges.push(
            h(Badge, { variant: 'secondary', class: 'mr-1' }, () => 'Officer'),
        );
    }

    if (candidate.is_administrator) {
        badges.push(
            h(
                Badge,
                { variant: 'secondary', class: 'mr-1' },
                () => 'Administrator',
            ),
        );
    }

    if (badges.length === 0) {
        return '—';
    }

    return h('div', { class: 'flex flex-wrap gap-1' }, badges);
}

export const administratorHolderColumns = columnHelper.columns([
    columnHelper.accessor('name', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Name' }),
        sortFn: 'text',
    }),
    columnHelper.accessor('email', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Email' }),
        sortFn: 'text',
    }),
]);

export const administratorCandidateColumns = columnHelper.columns([
    columnHelper.accessor('name', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Name' }),
        sortFn: 'text',
    }),
    columnHelper.accessor('email', {
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Email' }),
        sortFn: 'text',
    }),
    columnHelper.display({
        id: 'roles',
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Roles' }),
        cell: ({ row }) => roleBadges(row.original),
        enableSorting: false,
    }),
    columnHelper.display({
        id: 'actions',
        enableHiding: false,
        enableSorting: false,
        header: () => h('div', { class: 'text-right' }, 'Actions'),
        cell: ({ row }) =>
            h(AdministratorRowActions, { candidate: row.original }),
    }),
]);
