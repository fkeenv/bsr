<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import RichTextContent from '@/components/RichTextContent.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as announcementsIndex } from '@/routes/announcements';
import type { Announcement } from '@/types/announcement';

defineProps<{
    announcement: Announcement;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Announcements',
                href: announcementsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="announcement.title" />

    <div class="mx-auto flex max-w-3xl flex-col space-y-6 p-4">
        <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <Heading :title="announcement.title" />
                <Badge v-if="announcement.is_pinned" variant="outline">
                    Pinned
                </Badge>
            </div>
            <Button variant="ghost" class="px-0" as-child>
                <Link :href="announcementsIndex()">Back to feed</Link>
            </Button>
        </div>

        <RichTextContent :html="announcement.body" />

        <div
            v-if="announcement.attachments.length"
            class="space-y-3 border-t pt-6"
        >
            <h2 class="text-sm font-medium">Attachments</h2>
            <ul class="space-y-4">
                <li
                    v-for="attachment in announcement.attachments"
                    :key="attachment.id"
                >
                    <img
                        v-if="attachment.is_image"
                        :src="attachment.url"
                        :alt="attachment.original_filename"
                        class="max-h-96 max-w-full rounded-md border"
                    />
                    <a
                        v-else
                        :href="attachment.url"
                        class="text-primary underline-offset-4 hover:underline"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {{ attachment.original_filename }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</template>
