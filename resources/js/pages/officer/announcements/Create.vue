<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AnnouncementController from '@/actions/App/Http/Controllers/Officer/AnnouncementController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createAnnouncement,
    index as announcementsIndex,
} from '@/routes/officer/announcements';

const body = ref('');

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
            description="Drafts are shared with every Officer. Publish when ready for the Membership feed."
        />

        <Form
            v-bind="AnnouncementController.store.form()"
            class="max-w-3xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="title">Title</Label>
                <Input id="title" name="title" required />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="body">Body</Label>
                <input type="hidden" name="body" :value="body" />
                <RichTextEditor id="body" v-model="body" />
                <InputError :message="errors.body" />
            </div>

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
