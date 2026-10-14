<script setup lang="ts">
import { Form, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import EventFormFields from '@/pages/officer/events/EventFormFields.vue';
import { create, index, store } from '@/routes/officer/events';
const description = ref('<p></p>');
setLayoutProps({
    breadcrumbs: [
        { title: 'Events', href: index() },
        { title: 'New draft', href: create() },
    ],
});
</script>
<template>
    <div class="space-y-6 p-4 sm:p-6">
        <Head title="New Event" /><Heading
            title="New Event"
            description="Plan a free community gathering. Save a draft, then publish when you are ready."
        />
        <Form
            v-bind="store.form()"
            class="max-w-3xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <EventFormFields
                v-model:description="description"
                :errors="errors"
            />
            <div class="flex gap-3">
                <Button type="submit" :disabled="processing">Save draft</Button
                ><Button variant="ghost" as-child
                    ><Link :href="index()">Cancel</Link></Button
                >
            </div>
        </Form>
    </div>
</template>
