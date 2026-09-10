<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { dashboardMembershipColumns } from '@/pages/dashboard-membership-columns';
import { dashboard } from '@/routes';
import type { Membership } from '@/types/membership';

type Props = {
    memberships: Membership[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Dashboard"
            description="Your live Memberships. You may end one yourself, or apply for another Property from the sidebar."
        />

        <DataTable
            :columns="dashboardMembershipColumns"
            :data="memberships"
            :action="dashboard.url()"
            empty-text="You have no live Memberships."
        />
    </div>
</template>
