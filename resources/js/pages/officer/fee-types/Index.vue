<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { feeTypeColumns } from '@/pages/officer/fee-types/columns';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createFeeType,
    index as feeTypesIndex,
} from '@/routes/officer/fee-types';
import type { FeeType } from '@/types/fee-type';

type Props = {
    feeTypes: FeeType[];
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
                title: 'Fee Types',
                href: feeTypesIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Fee Types" />

    <div class="flex flex-col space-y-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Fee Types"
                description="Named dues the association levies on roster Properties."
            />

            <Button as-child>
                <Link :href="createFeeType()">Add Fee Type</Link>
            </Button>
        </div>

        <DataTable
            :columns="feeTypeColumns"
            :data="feeTypes"
            :action="feeTypesIndex.url()"
            empty-text="No Fee Types yet."
        />
    </div>
</template>
