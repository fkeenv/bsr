<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { paymentColumns } from '@/pages/officer/payments/columns';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createPayment,
    index as paymentsIndex,
} from '@/routes/officer/payments';
import type {
    DataTableFilterOption,
    DataTableValues,
} from '@/types/data-table';
import type { Payment } from '@/types/payment';

type Props = {
    payments: Payment[];
    table: {
        searchables: string[];
        filters: string[];
        dateRanges: string[];
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
                title: 'Payments',
                href: paymentsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Payments" />

    <div class="flex flex-col space-y-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Payments"
                description="Confirm or reject Member declarations, record confirmed Payments, or void with a reason."
            />

            <Button as-child>
                <Link :href="createPayment()">Record Payment</Link>
            </Button>
        </div>

        <DataTable
            :columns="paymentColumns"
            :data="payments"
            :action="paymentsIndex.url()"
            :searchables="table.searchables"
            :filters="table.filters"
            :date-ranges="table.dateRanges"
            :filter-options="table.filterOptions"
            :values="table.values"
            :searchable-labels="{
                reference: 'reference',
                property: 'Property',
                declarer: 'declarer',
            }"
            :date-range-labels="{
                recorded: 'Recorded',
            }"
            empty-text="No Payments yet."
        />
    </div>
</template>
