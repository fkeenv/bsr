<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AnnouncementController from '@/actions/App/Http/Controllers/Officer/AnnouncementController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createAnnouncement,
    edit as editAnnouncement,
    index as announcementsIndex,
} from '@/routes/officer/announcements';
import type { Announcement } from '@/types/announcement';

defineProps<{
    announcements: Announcement[];
}>();

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
        ],
    },
});
</script>

<template>
    <Head title="Announcements" />

    <div class="flex flex-col space-y-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Announcements"
                description="Shared drafts among Officers. Publish to the Membership feed and pin up to three."
            />

            <Button as-child>
                <Link :href="createAnnouncement()">New draft</Link>
            </Button>
        </div>

        <div
            v-if="announcements.length === 0"
            class="text-muted-foreground text-sm"
        >
            No Announcements yet.
        </div>

        <ul v-else class="divide-border divide-y border-y">
            <li
                v-for="announcement in announcements"
                :key="announcement.id"
                class="flex flex-col gap-3 py-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="min-w-0 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <Link
                            :href="editAnnouncement(announcement)"
                            class="font-medium hover:underline"
                        >
                            {{ announcement.title }}
                        </Link>
                        <Badge
                            v-if="announcement.is_published"
                            variant="default"
                        >
                            Published
                        </Badge>
                        <Badge v-else variant="secondary">Draft</Badge>
                        <Badge v-if="announcement.is_pinned" variant="outline">
                            Pinned
                        </Badge>
                    </div>
                    <p class="text-muted-foreground text-sm">
                        {{ announcement.attachments.length }}
                        attachment{{
                            announcement.attachments.length === 1 ? '' : 's'
                        }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="editAnnouncement(announcement)">
                            Edit
                        </Link>
                    </Button>

                    <Form
                        v-if="!announcement.is_published"
                        v-bind="
                            AnnouncementController.publish.form(announcement)
                        "
                    >
                        <Button type="submit" size="sm">Publish</Button>
                    </Form>

                    <Form
                        v-else
                        v-bind="
                            AnnouncementController.unpublish.form(announcement)
                        "
                    >
                        <Button type="submit" variant="secondary" size="sm">
                            Unpublish
                        </Button>
                    </Form>

                    <Form
                        v-if="
                            announcement.is_published && !announcement.is_pinned
                        "
                        v-bind="AnnouncementController.pin.form(announcement)"
                    >
                        <Button type="submit" variant="outline" size="sm">
                            Pin
                        </Button>
                    </Form>

                    <Form
                        v-if="announcement.is_pinned"
                        v-bind="AnnouncementController.unpin.form(announcement)"
                    >
                        <Button type="submit" variant="outline" size="sm">
                            Unpin
                        </Button>
                    </Form>
                </div>
            </li>
        </ul>
    </div>
</template>
