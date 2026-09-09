<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { suspendColumns } from '@/pages/officer/suspends/columns';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createSuspend,
    index as suspendsIndex,
} from '@/routes/officer/suspends';
import type { Suspend } from '@/types/suspend';

type Props = {
    suspends: Suspend[];
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
                title: 'Suspends',
                href: suspendsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Suspends" />

    <div class="flex flex-col space-y-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Suspends"
                description="Pause one Fee Type on one Property for Billing Periods."
            />

            <Button as-child>
                <Link :href="createSuspend()">Add Suspend</Link>
            </Button>
        </div>

        <DataTable
            :columns="suspendColumns"
            :data="suspends"
            :action="suspendsIndex.url()"
            empty-text="No Suspends yet."
        />
    </div>
</template>
