<script setup lang="ts">
import { Form, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { ref } from 'vue';
import AnnouncementController from '@/actions/App/Http/Controllers/Officer/AnnouncementController';
import Heading from '@/components/Heading.vue';
import { useAnnouncementPermissions } from '@/composables/useAnnouncementPermissions';
import { dashboard } from '@/routes';
import { Button } from '@/components/ui/button';
import AnnouncementFormFields from '@/pages/officer/announcements/AnnouncementFormFields.vue';
import {
    create as createAnnouncement,
    index as announcementsIndex,
} from '@/routes/officer/announcements';
import type { AnnouncementVisibilityOption } from '@/types/announcement';

defineProps<{ visibilityOptions: AnnouncementVisibilityOption[] }>();

const { allows } = useAnnouncementPermissions();
const body = ref('<p></p>');

setLayoutProps({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        ...(allows('announcements.read')
            ? [{ title: 'Announcements', href: announcementsIndex() }]
            : []),
        { title: 'New draft', href: createAnnouncement() },
    ],
});
</script>

<template>
    <div class="flex flex-col space-y-6 p-4">
        <Head title="New Announcement draft" />
        <Heading
            title="New draft"
            description="Drafts are shared with staff who have Announcement access. Write the body in the rich text editor, then save your draft."
        />

        <Form
            v-bind="AnnouncementController.store.form()"
            class="max-w-3xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <AnnouncementFormFields
                v-model:body="body"
                :errors="errors"
                :visibility-options="visibilityOptions"
            />

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">
                    Save draft
                </Button>
                <Button variant="ghost" as-child>
                    <Link
                        :href="
                            allows('announcements.read')
                                ? announcementsIndex()
                                : dashboard()
                        "
                        >Cancel</Link
                    >
                </Button>
            </div>
        </Form>
    </div>
</template>
