<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { columns } from './columns';
import { index } from '@/routes/administrator/user-accounts';
import type { DataTableValues } from '@/types/data-table';
import type { UserAccount } from '@/types/user-account';
const props = defineProps<{
    accounts: UserAccount[];
    pagination: { current_page: number; last_page: number; total: number };
    values: DataTableValues;
    staff_roles: string[];
}>();
const filterOptions = computed(() => ({
    staff_role: props.staff_roles.map((name) => ({
        value: name,
        label: name.replaceAll('-', ' '),
    })),
    membership_role: [
        { value: 'owner', label: 'Owner' },
        { value: 'resident', label: 'Resident' },
    ],
    live_membership: [
        { value: 'yes', label: 'With live Memberships' },
        { value: 'no', label: 'No live Memberships' },
    ],
}));
function pageUrl(page: number) {
    return index({ query: { ...props.values, page } });
}
defineOptions({
    layout: { breadcrumbs: [{ title: 'User Accounts', href: index() }] },
});
</script>
<template>
    <div class="flex flex-col gap-6 p-4">
        <Head title="User Accounts" />
        <Heading
            title="User Accounts"
            description="Find people, review their Property Memberships, and manage eligible staff assignments."
        />
        <DataTable
            :columns="columns"
            :data="accounts"
            :action="index.url()"
            :values="values"
            :searchables="['name', 'email', 'mobile', 'property']"
            :searchable-labels="{
                mobile: 'mobile number',
                property: 'block or lot',
            }"
            :filters="['staff_role', 'membership_role', 'live_membership']"
            :filter-options="filterOptions"
            :date-ranges="['registered']"
            :date-range-labels="{ registered: 'Registered' }"
            :filter-labels="{
                staff_role: 'Staff roles',
                membership_role: 'Property roles',
                live_membership: 'Membership status',
            }"
            empty-text="No User Accounts match these filters."
        />
        <nav
            aria-label="User Account pagination"
            class="flex flex-wrap items-center justify-between gap-3"
        >
            <p class="text-muted-foreground text-sm">
                {{ pagination.total }} accounts · Page
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
                >
                <Button v-else variant="outline" disabled>Previous</Button>
                <Button
                    v-if="pagination.current_page < pagination.last_page"
                    as-child
                    variant="outline"
                    ><Link
                        :href="pageUrl(pagination.current_page + 1)"
                        preserve-scroll
                        >Next</Link
                    ></Button
                >
                <Button v-else variant="outline" disabled>Next</Button>
            </div>
        </nav>
    </div>
</template>
