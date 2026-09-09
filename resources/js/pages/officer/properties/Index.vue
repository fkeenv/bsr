<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PropertyImportController from '@/actions/App/Http/Controllers/Officer/PropertyImportController';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { propertyColumns } from '@/pages/officer/properties/columns';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createProperty,
    index as propertiesIndex,
} from '@/routes/officer/properties';
import type {
    DataTableFilterOption,
    DataTableValues,
} from '@/types/data-table';
import type { Property } from '@/types/property';

type Props = {
    properties: Property[];
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
                title: 'Properties',
                href: propertiesIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Property roster" />

    <div class="flex flex-col space-y-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Property roster"
                description="Houses on the association roster, identified by Block and Lot."
            />

            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="PropertyImportController.create.url()">
                        Import CSV
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="createProperty()">Add Property</Link>
                </Button>
            </div>
        </div>

        <DataTable
            :columns="propertyColumns"
            :data="properties"
            :action="propertiesIndex.url()"
            :searchables="table.searchables"
            :filters="table.filters"
            :filter-options="table.filterOptions"
            :values="table.values"
            :searchable-labels="{
                name: 'owner name',
                block: 'block',
                lot: 'lot',
            }"
            :filter-labels="{
                status: 'Status',
                block: 'Block',
            }"
            empty-text="No Properties match these filters."
        />
    </div>
</template>
