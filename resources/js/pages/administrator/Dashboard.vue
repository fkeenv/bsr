<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { dashboard as administratorDashboard } from '@/routes/administrator';
import { index as officersIndex } from '@/routes/administrator/officers';

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
            description="Association leadership appointments."
        />

        <ul class="max-w-lg space-y-3 text-sm">
            <li>
                <Link
                    :href="officersIndex()"
                    class="font-medium underline-offset-4 hover:underline"
                >
                    Officers
                </Link>
            </li>
        </ul>
    </div>
</template>
