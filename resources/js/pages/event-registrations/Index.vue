<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import EventRegistrationList from '@/components/EventRegistrationList.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index as eventsIndex } from '@/routes/events';
import { index } from '@/routes/event-registrations';
import type { EventRegistration } from '@/types/event-registration';
defineProps<{ registrations: EventRegistration[] }>();
setLayoutProps({ breadcrumbs: [{ title: 'My registrations', href: index() }] });
</script>
<template>
    <div class="space-y-6 p-4 sm:p-6">
        <Head title="My Event registrations" />
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="My registrations"
                description="Your Event registrations and attendee tickets."
            /><Button as-child
                ><Link :href="eventsIndex()">Find Events</Link></Button
            >
        </div>
        <EventRegistrationList :registrations="registrations" />
    </div>
</template>
