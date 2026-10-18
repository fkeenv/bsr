<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import EventFormFields from '@/pages/officer/events/EventFormFields.vue';
import { useEventEditor } from './useEventEditor';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { create, index } from '@/routes/officer/events';
const { form, element, imageRevision, submit } = useEventEditor();
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
            description="Plan a free or paid community gathering. Save a draft, then publish when you are ready."
        />
        <form
            ref="element"
            class="max-w-3xl space-y-5"
            @submit.prevent="submit"
        >
            <Alert
                v-if="form.hasErrors"
                variant="destructive"
                role="alert"
                data-event-error="true"
                tabindex="-1"
                ><AlertTitle>Check the Event details</AlertTitle
                ><AlertDescription
                    >Review the errors below. Your changes have been
                    kept.</AlertDescription
                ></Alert
            >
            <fieldset :disabled="form.processing" class="min-w-0 space-y-5">
                <EventFormFields :form="form" :image-revision="imageRevision" />
                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing"
                        >Save draft</Button
                    ><Button variant="ghost" as-child
                        ><Link :href="index()">Cancel</Link></Button
                    >
                </div>
            </fieldset>
        </form>
    </div>
</template>
