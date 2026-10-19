<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/officer/event-finances';
import type { EventFinancePage } from '@/types/event-finance';
import { eventFinanceColumns } from './columns';

const props = defineProps<EventFinancePage>();
const columns = computed(() =>
    eventFinanceColumns(props.canReviewPayments, props.canReviewRefunds),
);
const currentWork = computed(() => props.table.values.work ?? 'attention');
function pageLink(page: number) {
    return index({ query: { ...props.table.values, page } });
}
setLayoutProps({
    breadcrumbs: [{ title: 'Event payments & refunds', href: index() }],
});
</script>
<template>
    <div class="space-y-6 p-4 sm:p-6">
        <Head title="Event payments & refunds" />
        <Heading
            title="Event payments & refunds"
            description="See what needs a decision, record money already returned, or review previous activity. Event finances are separate from Property dues."
        />
        <nav aria-label="Event financial work" class="flex flex-wrap gap-2">
            <Button
                v-for="option in workOptions"
                :key="option.value"
                as-child
                :variant="currentWork === option.value ? 'default' : 'outline'"
                size="sm"
            >
                <Link
                    :href="
                        index({
                            query: { ...table.values, work: option.value },
                        })
                    "
                    :aria-current="
                        currentWork === option.value ? 'page' : undefined
                    "
                    preserve-scroll
                >
                    {{ option.label }}
                    <Badge variant="secondary" class="ml-1 tabular-nums">{{
                        option.count
                    }}</Badge>
                </Link>
            </Button>
        </nav>
        <p class="text-muted-foreground text-sm">
            A registration can need more than one decision. Approving
            cancellation does not mean money has been refunded. Choose All
            registrations to review completed history.
        </p>
        <DataTable
            :columns="columns"
            :data="registrations"
            :action="index.url({ query: { work: currentWork } })"
            :searchables="table.searchables"
            :filters="table.filters"
            :filter-options="table.filterOptions"
            :values="table.values"
            :searchable-labels="{
                event: 'Event',
                account: 'name or email',
                reference: 'payment reference',
                registration: 'registration number',
            }"
            :filter-labels="{ event: 'Events' }"
            empty-text="No registrations match this view. Adjust the search or filters, or choose All registrations to see history."
        />
        <nav
            aria-label="Event finance pages"
            class="flex flex-wrap items-center justify-between gap-3"
        >
            <p class="text-muted-foreground text-sm">
                {{ pagination.total }} registrations · Page
                {{ pagination.current_page }} of {{ pagination.last_page }}
            </p>
            <div class="flex gap-2">
                <Button
                    v-if="pagination.current_page > 1"
                    variant="outline"
                    as-child
                    ><Link
                        :href="pageLink(pagination.current_page - 1)"
                        preserve-scroll
                        >Previous</Link
                    ></Button
                >
                <Button v-else variant="outline" disabled>Previous</Button>
                <Button
                    v-if="pagination.current_page < pagination.last_page"
                    variant="outline"
                    as-child
                    ><Link
                        :href="pageLink(pagination.current_page + 1)"
                        preserve-scroll
                        >Next</Link
                    ></Button
                >
                <Button v-else variant="outline" disabled>Next</Button>
            </div>
        </nav>
    </div>
</template>
