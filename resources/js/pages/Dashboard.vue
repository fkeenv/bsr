<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import MembershipController from '@/actions/App/Http/Controllers/MembershipController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type Membership = {
    id: number;
    role: string;
    property_label: string | null;
};

type Props = {
    memberships: Membership[];
};

defineProps<Props>();

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
            description="Your live Memberships. You may end one yourself, or apply for another Property from the sidebar."
        />

        <div
            v-if="memberships.length === 0"
            class="text-muted-foreground text-sm"
        >
            You have no live Memberships.
        </div>

        <ul v-else class="divide-border divide-y border-y">
            <li
                v-for="membership in memberships"
                :key="membership.id"
                class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="font-medium">{{ membership.property_label }}</p>
                    <p class="text-muted-foreground text-sm">
                        {{ membership.role }}
                    </p>
                </div>

                <Form
                    v-bind="MembershipController.end.form(membership.id)"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                    >
                        End Membership
                    </Button>
                </Form>
            </li>
        </ul>
    </div>
</template>
