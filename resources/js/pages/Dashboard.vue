<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { HousePlus } from '@lucide/vue';
import { computed } from 'vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { dashboardMembershipColumns } from '@/pages/dashboard-membership-columns';
import { dashboard, joinProperty } from '@/routes';
import type { Membership } from '@/types/membership';

type Props = {
    memberships: Membership[];
};

defineProps<Props>();

const page = usePage();
const canJoinProperty = computed(
    () => page.props.auth.capabilities?.isSuperAdmin !== true,
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Dashboard"
            description="Your live Memberships. You may end one yourself, or join another Property with an invitation link."
        />

        <div
            v-if="memberships.length === 0 && canJoinProperty"
            class="flex flex-col items-start gap-3 rounded-md border border-dashed p-6"
        >
            <div class="grid gap-1">
                <h2 class="text-base font-medium">
                    You have not joined a Property yet
                </h2>
                <p class="text-muted-foreground text-sm">
                    Ask an Officer for a Property Invitation, then open the link
                    to join.
                </p>
            </div>
            <Button as-child>
                <Link :href="joinProperty()">
                    <HousePlus class="size-4" />
                    Join a Property
                </Link>
            </Button>
        </div>

        <DataTable
            v-else
            :columns="dashboardMembershipColumns"
            :data="memberships"
            :action="dashboard.url()"
            empty-text="You have no live Memberships."
        />
    </div>
</template>
