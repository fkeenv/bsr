<script setup lang="ts">
import { computed, ref } from 'vue';
import { Paperclip, Pin } from '@lucide/vue';
import { Form, Head, Link } from '@inertiajs/vue3';
import AnnouncementController from '@/actions/App/Http/Controllers/Officer/AnnouncementController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import AnnouncementActions from './AnnouncementActions.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createAnnouncement,
    edit as editAnnouncement,
    index as announcementsIndex,
} from '@/routes/officer/announcements';
import type { Announcement } from '@/types/announcement';

type VisibilityOption = {
    value: string;
    label: string;
    description: string;
};

const props = defineProps<{
    announcements: Announcement[];
    pageVisibility: string;
    pageVisibilityOptions: VisibilityOption[];
}>();

const views = ['All', 'Published', 'Drafts', 'Pinned'] as const;
type View = (typeof views)[number];
const selectedView = ref<View>('All');
const visibleAnnouncements = computed(() =>
    props.announcements.filter((announcement) => {
        switch (selectedView.value) {
            case 'Published':
                return announcement.is_published;
            case 'Drafts':
                return !announcement.is_published;
            case 'Pinned':
                return announcement.is_published && announcement.is_pinned;
            default:
                return true;
        }
    }),
);
const dateFormatter = new Intl.DateTimeFormat('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'Asia/Manila',
});
const formatDate = (date: string) => dateFormatter.format(new Date(date));
const visibilityLabel = computed(
    () =>
        props.pageVisibilityOptions.find(
            (option) => option.value === props.pageVisibility,
        )?.label,
);

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
    <div
        class="mx-auto flex w-full max-w-7xl flex-col gap-7 px-5 py-8 sm:px-8 sm:py-12 lg:px-16"
    >
        <Head title="Announcements" />
        <header
            class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <p
                    class="text-muted-foreground mb-3 text-xs font-medium tracking-widest uppercase"
                >
                    Community communications
                </p>
                <Heading
                    title="Announcements"
                    description="A shared space to draft, publish, and keep everyone informed."
                />
            </div>
            <Button
                as-child
                class="h-auto min-h-11 self-start px-5 py-3 sm:self-center"
            >
                <Link :href="createAnnouncement()">New draft</Link>
            </Button>
        </header>
        <details class="bg-muted/60 rounded-lg border">
            <summary
                class="focus-visible:outline-ring flex min-h-14 cursor-pointer flex-wrap items-center justify-between gap-2 rounded-lg px-5 py-4 text-sm focus-visible:outline-2 focus-visible:outline-offset-4 sm:px-6"
            >
                <span
                    >Feed visibility
                    <span class="text-muted-foreground"
                        >· {{ visibilityLabel }}</span
                    ></span
                >
                <span class="text-primary font-medium">Manage visibility</span>
            </summary>
            <Form
                v-bind="AnnouncementController.updatePageVisibility.form()"
                class="space-y-4 border-t p-5 sm:p-6"
                :options="{ preserveScroll: true }"
                v-slot="{ errors, processing }"
            >
                <fieldset class="space-y-3" :disabled="processing">
                    <legend class="sr-only">
                        Announcements page visibility
                    </legend>
                    <div
                        v-for="option in pageVisibilityOptions"
                        :key="option.value"
                        class="flex items-start gap-3"
                    >
                        <input
                            :id="`visibility-${option.value}`"
                            type="radio"
                            name="announcements_page_visibility"
                            :value="option.value"
                            :checked="pageVisibility === option.value"
                            class="border-input text-primary mt-1 size-4"
                        />
                        <Label
                            :for="`visibility-${option.value}`"
                            class="grid gap-1 font-normal"
                        >
                            <span class="font-medium">{{ option.label }}</span>
                            <span class="text-muted-foreground text-sm">
                                {{ option.description }}
                            </span>
                        </Label>
                    </div>
                </fieldset>
                <div v-if="errors.announcements_page_visibility" role="alert">
                    <InputError
                        :message="errors.announcements_page_visibility"
                    />
                </div>
                <Button type="submit" :disabled="processing">
                    {{ processing ? 'Saving visibility…' : 'Save visibility' }}
                </Button>
            </Form>
        </details>
        <section aria-label="Announcement workspace">
            <div
                class="flex flex-col gap-2 border-b sm:flex-row sm:items-center sm:justify-between"
            >
                <div
                    role="group"
                    aria-label="Announcement views"
                    class="flex flex-wrap gap-x-6"
                >
                    <button
                        v-for="view in views"
                        :key="view"
                        type="button"
                        :aria-pressed="selectedView === view"
                        @click="selectedView = view"
                        class="focus-visible:outline-ring min-h-12 border-b-2 px-1 py-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2"
                        :class="
                            selectedView === view
                                ? 'border-primary text-primary font-medium'
                                : 'text-muted-foreground hover:text-foreground border-transparent'
                        "
                    >
                        {{ view }}
                    </button>
                </div>
                <p class="text-muted-foreground pb-3 text-xs sm:pb-0">
                    Pin up to 3 published Announcements
                </p>
            </div>
            <div aria-live="polite" class="sr-only">
                {{ visibleAnnouncements.length }} Announcements in
                {{ selectedView }}
            </div>
            <div
                v-if="visibleAnnouncements.length === 0"
                class="py-14 text-center"
            >
                <h2 class="font-serif text-2xl">
                    {{
                        announcements.length === 0
                            ? 'No Announcements yet'
                            : `No ${selectedView.toLowerCase()} Announcements`
                    }}
                </h2>
                <p class="text-muted-foreground mt-3 text-sm">
                    {{
                        announcements.length === 0
                            ? 'Start a shared draft when you have something to tell the community.'
                            : 'Try another view or create a new draft.'
                    }}
                </p>
            </div>
            <ul v-else class="divide-border divide-y">
                <li
                    v-for="announcement in visibleAnnouncements"
                    :key="announcement.id"
                    class="flex flex-col gap-5 py-6 sm:flex-row sm:items-start sm:gap-7"
                >
                    <div class="min-w-0 flex-1 space-y-3">
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <span
                                class="rounded-full px-3 py-1"
                                :class="
                                    announcement.is_published
                                        ? 'bg-accent text-accent-foreground'
                                        : 'bg-muted text-muted-foreground'
                                "
                                >{{
                                    announcement.is_published
                                        ? 'Published'
                                        : 'Draft'
                                }}</span
                            >
                            <span
                                v-if="announcement.is_pinned"
                                class="text-primary inline-flex items-center gap-1"
                                ><Pin
                                    class="size-3.5"
                                    aria-hidden="true"
                                />Pinned</span
                            >
                        </div>
                        <h2
                            class="font-serif text-2xl leading-snug break-words sm:text-[26px]"
                        >
                            <Link
                                :href="editAnnouncement(announcement)"
                                class="focus-visible:outline-ring rounded-sm hover:underline focus-visible:outline-2"
                                >{{ announcement.title }}</Link
                            >
                        </h2>
                        <p
                            class="text-muted-foreground text-sm leading-6 break-words"
                        >
                            {{ announcement.excerpt }}
                        </p>
                        <div
                            class="text-muted-foreground flex flex-wrap items-center gap-x-6 gap-y-2 text-xs"
                        >
                            <span
                                v-if="
                                    announcement.is_published &&
                                    announcement.published_at
                                "
                                >Published
                                <time :datetime="announcement.published_at">{{
                                    formatDate(announcement.published_at)
                                }}</time></span
                            >
                            <span v-else-if="announcement.updated_at"
                                >Updated
                                <time :datetime="announcement.updated_at">{{
                                    formatDate(announcement.updated_at)
                                }}</time></span
                            >
                            <span class="inline-flex items-center gap-1.5"
                                ><Paperclip
                                    class="size-3.5"
                                    aria-hidden="true"
                                />{{
                                    announcement.attachments.length === 0
                                        ? 'No attachments'
                                        : `${announcement.attachments.length} attachment${announcement.attachments.length === 1 ? '' : 's'}`
                                }}</span
                            >
                        </div>
                    </div>
                    <AnnouncementActions :announcement="announcement" />
                </li>
            </ul>
        </section>
    </div>
</template>
