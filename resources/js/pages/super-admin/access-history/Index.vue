<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { columns } from './columns';
import { dashboard } from '@/routes/super-admin';
import { index } from '@/routes/super-admin/access-history';
import type {
    AccessHistoryEntry,
    AccessHistoryFilters,
} from '@/types/access-history';
import type { DataTableValues } from '@/types/data-table';
const props = defineProps<{
    entries: AccessHistoryEntry[];
    pagination: { current_page: number; last_page: number; total: number };
    values: DataTableValues;
    filter_options: AccessHistoryFilters;
    permission_labels: Record<string, string>;
}>();
const tableColumns = computed(() =>
    columns(
        props.permission_labels,
        Object.fromEntries(
            props.filter_options.action.map((option) => [
                option.value,
                option.label,
            ]),
        ),
    ),
);
function pageUrl(page: number) {
    return index({ query: { ...props.values, page } });
}
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Super Admin', href: dashboard() },
            { title: 'Access history', href: index() },
        ],
    },
});
</script>
<template>
    <div class="flex flex-col gap-6 p-4">
        <Head title="Access history" />
        <Heading
            title="Access history"
            description="Review recorded role definitions, permissions, staff assignments, and automatic removals. This history is read-only."
        />
        <DataTable
            :columns="tableColumns"
            :data="entries"
            :action="index.url()"
            :values="values"
            :filters="['actor', 'account', 'role', 'action']"
            :filter-options="filter_options"
            :filter-labels="{
                actor: 'Actors',
                account: 'Affected accounts',
                role: 'Affected roles',
                action: 'Changes',
            }"
            :date-ranges="['occurred']"
            :date-range-labels="{ occurred: 'When' }"
            empty-text="No recorded access changes match these filters."
        />
        <nav
            aria-label="Access history pagination"
            class="flex flex-wrap items-center justify-between gap-3"
        >
            <p class="text-muted-foreground text-sm">
                {{ pagination.total }} changes · Page
                {{ pagination.current_page }} of {{ pagination.last_page }}
            </p>
            <div class="flex gap-2">
                <Button
                    v-if="pagination.current_page > 1"
                    as-child
                    variant="outline"
                    ><Link
                        :href="pageUrl(pagination.current_page - 1)"
                        preserve-scroll
                        >Previous</Link
                    ></Button
                ><Button v-else variant="outline" disabled>Previous</Button
                ><Button
                    v-if="pagination.current_page < pagination.last_page"
                    as-child
                    variant="outline"
                    ><Link
                        :href="pageUrl(pagination.current_page + 1)"
                        preserve-scroll
                        >Next</Link
                    ></Button
                ><Button v-else variant="outline" disabled>Next</Button>
            </div>
        </nav>
    </div>
</template>
