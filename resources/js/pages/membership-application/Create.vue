<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { create as membershipApplicationCreate } from '@/routes/membership-application';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Membership Application',
                href: membershipApplicationCreate(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Membership Application" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Membership Application"
            description="Apply to be recognised on a Property. The full form arrives in a later ticket."
        />
    </div>
</template>
