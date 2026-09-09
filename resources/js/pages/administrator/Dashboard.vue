<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { dashboard as administratorDashboard } from '@/routes/administrator';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Administrator',
                href: administratorDashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Administrator" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Administrator"
            description="Administrator surfaces will hang off this shell in later tickets."
        />
    </div>
</template>
