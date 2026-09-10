<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import {
    administratorCandidateColumns,
    administratorHolderColumns,
} from '@/pages/super-admin/administrators/columns';
import { dashboard as superAdminDashboard } from '@/routes/super-admin';
import { index as administratorsIndex } from '@/routes/super-admin/administrators';
import type { PlatformRoleCandidate } from '@/types/platform-role-candidate';

type Props = {
    candidates: PlatformRoleCandidate[];
    administrators: PlatformRoleCandidate[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Super Admin',
                href: superAdminDashboard(),
            },
            {
                title: 'Administrators',
                href: administratorsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Administrators" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Administrators"
            description="Appoint Administrators from users with a live owner Membership."
        />

        <section class="space-y-3">
            <h2 class="text-sm font-medium">Current Administrators</h2>
            <DataTable
                :columns="administratorHolderColumns"
                :data="administrators"
                :action="administratorsIndex.url()"
                empty-text="No Administrators appointed yet."
            />
        </section>

        <section class="space-y-3">
            <h2 class="text-sm font-medium">Eligible owners</h2>
            <DataTable
                :columns="administratorCandidateColumns"
                :data="candidates"
                :action="administratorsIndex.url()"
                empty-text="No live owner Memberships to appoint from."
            />
        </section>
    </div>
</template>
