<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { index as userAccountsIndex } from '@/routes/administrator/user-accounts';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import {
    officerCandidateColumns,
    officerHolderColumns,
} from '@/pages/administrator/officers/columns';
import { dashboard as administratorDashboard } from '@/routes/administrator';
import { index as officersIndex } from '@/routes/administrator/officers';
import type { PlatformRoleCandidate } from '@/types/platform-role-candidate';

type Props = {
    candidates: PlatformRoleCandidate[];
    officers: PlatformRoleCandidate[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Administrator',
                href: administratorDashboard(),
            },
            {
                title: 'Officers',
                href: officersIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Officers" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Officers"
            description="Appoint Officers from users with a live owner Membership."
        />

        <p class="text-muted-foreground text-sm">
            Manage staff assignments and revocations in the User Account
            directory.
        </p>
        <Button as-child variant="outline" class="self-start"
            ><Link :href="userAccountsIndex()">Open User Accounts</Link></Button
        >

        <section class="space-y-3">
            <h2 class="text-sm font-medium">Current Officers</h2>
            <DataTable
                :columns="officerHolderColumns"
                :data="officers"
                :action="officersIndex.url()"
                empty-text="No Officers appointed yet."
            />
        </section>

        <section class="space-y-3">
            <h2 class="text-sm font-medium">Eligible owners</h2>
            <DataTable
                :columns="officerCandidateColumns"
                :data="candidates"
                :action="officersIndex.url()"
                empty-text="No live owner Memberships to appoint from."
            />
        </section>
    </div>
</template>
