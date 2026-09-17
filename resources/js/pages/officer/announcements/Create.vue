<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AnnouncementController from '@/actions/App/Http/Controllers/Officer/AnnouncementController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AnnouncementFormFields from '@/pages/officer/announcements/AnnouncementFormFields.vue';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createAnnouncement,
    index as announcementsIndex,
} from '@/routes/officer/announcements';

const body = ref('<p></p>');

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
            {
                title: 'Announcements',
                href: announcementsIndex(),
            },
            {
                title: 'New draft',
                href: createAnnouncement(),
            },
        ],
    },
});
</script>

<template>
    <Head title="New Announcement draft" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="New draft"
            description="Drafts are shared with every Officer. Write the body in the rich text editor, then publish when ready."
        />

        <Form
            v-bind="AnnouncementController.store.form()"
            class="max-w-3xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <AnnouncementFormFields v-model:body="body" :errors="errors" />

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">
                    Save draft
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="announcementsIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
