<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    index as membershipApplicationsIndex,
    show as membershipApplicationShow,
} from '@/routes/officer/membership-applications';

type Application = {
    id: number;
    status: string;
    property_label: string | null;
    applicant_name: string | null;
    applicant_email: string | null;
};

type Props = {
    applications: Application[];
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
                title: 'Membership Applications',
                href: membershipApplicationsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Membership Applications" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Membership Applications"
            description="Review pending and rejected applications. Approve with an owner or resident role."
        />

        <div
            v-if="applications.length === 0"
            class="text-muted-foreground text-sm"
        >
            No Membership Applications need review.
        </div>

        <ul v-else class="divide-border divide-y border-y">
            <li
                v-for="application in applications"
                :key="application.id"
                class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="font-medium">
                        {{ application.applicant_name }}
                    </p>
                    <p class="text-muted-foreground text-sm">
                        {{ application.property_label }} ·
                        {{ application.status }}
                    </p>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="membershipApplicationShow(application.id)">
                        Review
                    </Link>
                </Button>
            </li>
        </ul>
    </div>
</template>
