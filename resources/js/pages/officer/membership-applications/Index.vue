<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import {
    membershipApplicationColumns,
    type MembershipApplicationRow,
} from '@/pages/officer/membership-applications/columns';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as membershipApplicationsIndex } from '@/routes/officer/membership-applications';
import type {
    DataTableFilterOption,
    DataTableValues,
} from '@/types/data-table';

type Props = {
    applications: MembershipApplicationRow[];
    table: {
        searchables: string[];
        filters: string[];
        filterOptions: Record<string, DataTableFilterOption[]>;
        values: DataTableValues;
    };
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
            {
                title: 'Membership Applications',
                href: membershipApplicationsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Membership Applications" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Membership Applications"
            description="Review pending and rejected applications. Approve with an owner or resident role."
        />

        <DataTable
            :columns="membershipApplicationColumns"
            :data="applications"
            :action="membershipApplicationsIndex.url()"
            :searchables="table.searchables"
            :filters="table.filters"
            :filter-options="table.filterOptions"
            :values="table.values"
            :searchable-labels="{
                name: 'applicant',
                email: 'email',
                property: 'Property',
            }"
            empty-text="No Membership Applications need review."
        />
    </div>
</template>
