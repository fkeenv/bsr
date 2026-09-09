<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { dashboard as officerDashboard } from '@/routes/officer';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Officer" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Officer"
            description="Officer surfaces will hang off this shell in later tickets."
        />
    </div>
</template>
