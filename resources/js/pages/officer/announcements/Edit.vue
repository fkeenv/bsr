<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AnnouncementAttachmentController from '@/actions/App/Http/Controllers/Officer/AnnouncementAttachmentController';
import AnnouncementController from '@/actions/App/Http/Controllers/Officer/AnnouncementController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import RichTextContent from '@/components/RichTextContent.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AnnouncementFormFields from '@/pages/officer/announcements/AnnouncementFormFields.vue';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as announcementsIndex } from '@/routes/officer/announcements';
import type { Announcement } from '@/types/announcement';

const props = defineProps<{
    announcement: Announcement;
}>();

const body = ref(props.announcement.body);

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
                title: 'Edit',
                href: announcementsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${announcement.title}`" />

    <div class="flex flex-col space-y-6 p-4">
        <div class="flex flex-wrap items-center gap-2">
            <Heading
                :title="announcement.title"
                description="Edit with the rich text editor. Attach images or PDFs below."
            />
            <Badge v-if="announcement.is_published" variant="default">
                Published
            </Badge>
            <Badge v-else variant="secondary">Draft</Badge>
            <Badge v-if="announcement.is_pinned" variant="outline">
                Pinned
            </Badge>
        </div>

        <Form
            v-bind="AnnouncementController.update.form(announcement)"
            class="max-w-3xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <AnnouncementFormFields
                :title="announcement.title"
                v-model:body="body"
                :errors="errors"
            />

            <div class="flex flex-wrap gap-2">
                <Button type="submit" :disabled="processing">
                    Save changes
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="announcementsIndex()">Back</Link>
                </Button>
            </div>
        </Form>

        <div class="max-w-3xl space-y-4 border-t pt-6">
            <Heading
                title="Attachments"
                description="Images (JPEG, PNG, WebP) and PDFs, up to 10 MB each."
            />

            <ul
                v-if="announcement.attachments.length"
                class="space-y-2 text-sm"
            >
                <li
                    v-for="attachment in announcement.attachments"
                    :key="attachment.id"
                >
                    <a
                        :href="attachment.url"
                        class="text-primary underline-offset-4 hover:underline"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {{ attachment.original_filename }}
                    </a>
                </li>
            </ul>
            <p v-else class="text-muted-foreground text-sm">
                No attachments yet.
            </p>

            <Form
                v-bind="
                    AnnouncementAttachmentController.store.form(announcement)
                "
                enctype="multipart/form-data"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="attachments">Upload files</Label>
                    <Input
                        id="attachments"
                        name="attachments[]"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,application/pdf"
                        multiple
                        required
                    />
                    <InputError :message="errors.attachments" />
                    <InputError :message="errors['attachments.0']" />
                </div>
                <Button type="submit" :disabled="processing">
                    Upload attachments
                </Button>
            </Form>
        </div>

        <div
            v-if="announcement.is_published"
            class="max-w-3xl space-y-2 border-t pt-6"
        >
            <p class="text-muted-foreground text-sm">Published preview</p>
            <RichTextContent :html="announcement.body" />
        </div>
    </div>
</template>
