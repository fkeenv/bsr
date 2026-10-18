<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import ContentImage from '@/components/ContentImage.vue';
import RichTextContent from '@/components/RichTextContent.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as announcementsIndex } from '@/routes/announcements';
import type { Announcement } from '@/types/announcement';

defineOptions({ layout: [] });

const props = defineProps<{
    announcement: Announcement;
    isPublicVisitor: boolean;
}>();

const coverImage = computed(() =>
    props.announcement.attachments.find((attachment) => attachment.is_image),
);
const attachments = computed(() =>
    props.announcement.attachments.filter(
        (attachment) => attachment.id !== coverImage.value?.id,
    ),
);

function goBack(): void {
    if (window.history.length > 1) {
        window.history.back();
        return;
    }

    router.visit(announcementsIndex());
}
</script>

<template>
    <div class="bg-background text-foreground min-h-screen">
        <Head :title="announcement.title" />

        <header class="border-border/60 border-b">
            <div
                class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-6 py-6 sm:px-10"
            >
                <Link
                    :href="home()"
                    class="focus-visible:outline-ring rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4"
                >
                    <span class="block font-serif text-xl sm:text-2xl">
                        Blessed Sacrament Residences
                    </span>
                    <span class="text-muted-foreground text-xs sm:text-sm">
                        Homeowners Association
                    </span>
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-6 py-12 sm:px-10 sm:py-16">
            <Button
                variant="outline"
                size="sm"
                class="mb-8 sm:mb-10"
                type="button"
                @click="goBack"
            >
                <ArrowLeft aria-hidden="true" />
                Back
            </Button>
            <article
                aria-labelledby="announcement-title"
                class="space-y-8 sm:space-y-10"
            >
                <header class="space-y-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <span
                            class="text-primary text-xs font-medium tracking-widest uppercase"
                        >
                            Community news
                        </span>
                        <Badge v-if="announcement.is_pinned" variant="outline">
                            Pinned
                        </Badge>
                    </div>
                    <h1
                        id="announcement-title"
                        class="font-serif text-4xl leading-tight font-normal break-words sm:text-5xl"
                    >
                        {{ announcement.title }}
                    </h1>
                    <time
                        v-if="announcement.published_at"
                        :datetime="announcement.published_at"
                        class="text-muted-foreground block text-sm"
                    >
                        {{
                            new Date(
                                announcement.published_at,
                            ).toLocaleDateString('en-PH', {
                                timeZone: 'Asia/Manila',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                            })
                        }}
                    </time>
                </header>

                <ContentImage
                    v-if="coverImage"
                    :src="coverImage.url"
                    :alt="coverImage.original_filename"
                    as="figure"
                    fit="contain"
                    loading="eager"
                    class="rounded-xl"
                >
                    <template #fallback>
                        <a
                            :href="coverImage.url"
                            class="text-primary focus-visible:outline-ring rounded-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4"
                            target="_blank"
                            rel="noopener noreferrer"
                            >Open {{ coverImage.original_filename }}</a
                        >
                    </template>
                </ContentImage>

                <RichTextContent
                    :html="announcement.body"
                    class="[&_a]:text-primary text-base leading-8 break-words [&_a]:underline [&_a]:underline-offset-4 [&_h2]:font-serif [&_h2]:text-2xl [&_h3]:font-serif [&_h3]:text-xl"
                />

                <section
                    v-if="attachments.length"
                    aria-labelledby="announcement-attachments-heading"
                    class="border-border space-y-5 border-t pt-8"
                >
                    <h2
                        id="announcement-attachments-heading"
                        class="font-serif text-2xl"
                    >
                        Attachments
                    </h2>
                    <ul class="space-y-5">
                        <li
                            v-for="attachment in attachments"
                            :key="attachment.id"
                        >
                            <ContentImage
                                v-if="attachment.is_image"
                                :src="attachment.url"
                                :alt="attachment.original_filename"
                                fit="contain"
                                class="rounded-lg"
                            >
                                <template #fallback>
                                    <a
                                        :href="attachment.url"
                                        class="text-primary focus-visible:outline-ring rounded-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        >Open
                                        {{ attachment.original_filename }}</a
                                    >
                                </template>
                            </ContentImage>
                            <a
                                v-else
                                :href="attachment.url"
                                class="text-primary focus-visible:outline-ring rounded-sm break-words underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{ attachment.original_filename }}
                            </a>
                        </li>
                    </ul>
                </section>
            </article>
        </main>
    </div>
</template>
