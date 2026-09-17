<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import RichTextContent from '@/components/RichTextContent.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import {
    index as announcementsIndex,
    show as announcementShow,
} from '@/routes/announcements';
import type { Announcement } from '@/types/announcement';

const props = defineProps<{
    announcements: Announcement[];
    filters: {
        search: string;
    };
}>();

const search = ref(props.filters.search);

watch(
    () => props.filters.search,
    (value) => {
        search.value = value;
    },
);

function submitSearch(): void {
    router.get(
        announcementsIndex.url({
            query: {
                search: search.value || undefined,
            },
        }),
        {},
        {
            preserveState: true,
            replace: true,
        },
    );
}

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
    <Head title="Announcements" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Announcements"
            description="Published notices from the association. Pinned items stay at the top."
        />

        <form
            class="flex max-w-xl flex-col gap-2 sm:flex-row"
            @submit.prevent="submitSearch"
        >
            <Input
                v-model="search"
                type="search"
                placeholder="Search title and body"
                aria-label="Search Announcements"
            />
            <Button type="submit">Search</Button>
        </form>

        <div
            v-if="announcements.length === 0"
            class="text-muted-foreground text-sm"
        >
            No published Announcements match this search.
        </div>

        <ul v-else class="divide-border divide-y border-y">
            <li
                v-for="announcement in announcements"
                :key="announcement.id"
                class="space-y-3 py-5"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="announcementShow(announcement)"
                        class="text-lg font-medium hover:underline"
                    >
                        {{ announcement.title }}
                    </Link>
                    <Badge v-if="announcement.is_pinned" variant="outline">
                        Pinned
                    </Badge>
                </div>
                <div class="line-clamp-3">
                    <RichTextContent :html="announcement.body" />
                </div>
                <Button variant="link" class="h-auto px-0" as-child>
                    <Link :href="announcementShow(announcement)">
                        Read more
                    </Link>
                </Button>
            </li>
        </ul>
    </div>
</template>
