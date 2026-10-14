<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import EventRegistrationList from '@/components/EventRegistrationList.vue';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/officer/event-registrations';
import type { EventRegistration } from '@/types/event-registration';
defineProps<{ registrations: EventRegistration[] }>();
setLayoutProps({
    breadcrumbs: [{ title: 'Event registrations', href: index() }],
});
</script>
<template>
    <div class="space-y-6 p-4 sm:p-6">
        <Head title="Event registrations" /><Heading
            title="Event registrations"
            description="Read registration details and attendee tickets."
        /><EventRegistrationList :registrations="registrations" />
    </div>
</template>
