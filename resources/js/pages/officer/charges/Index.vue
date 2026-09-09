<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { chargeColumns } from '@/pages/officer/charges/columns';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as chargesIndex } from '@/routes/officer/charges';
import { create as generateCharges } from '@/routes/officer/charges/generate';
import type { Charge } from '@/types/charge';

type Props = {
    charges: Charge[];
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
                title: 'Charges',
                href: chargesIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Charges" />

    <div class="flex flex-col space-y-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Charges"
                description="Levied amounts per Property and Billing Period."
            />

            <Button as-child>
                <Link :href="generateCharges()">Generate Charges</Link>
            </Button>
        </div>

        <DataTable
            :columns="chargeColumns"
            :data="charges"
            :action="chargesIndex.url()"
            empty-text="No Charges yet."
        />
    </div>
</template>
