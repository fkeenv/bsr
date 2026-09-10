<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { membershipColumns } from '@/pages/officer/memberships/columns';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as membershipsIndex } from '@/routes/officer/memberships';
import type { Membership } from '@/types/membership';

type Props = {
    memberships: Membership[];
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
                title: 'Memberships',
                href: membershipsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Memberships" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Memberships"
            description="Live Memberships across the roster. Change owner/resident role or end a Membership."
        />

        <DataTable
            :columns="membershipColumns"
            :data="memberships"
            :action="membershipsIndex.url()"
            empty-text="No live Memberships yet."
        />
    </div>
</template>
