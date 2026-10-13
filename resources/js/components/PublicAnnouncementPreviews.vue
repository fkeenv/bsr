<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AnnouncementPreviewCard from '@/components/AnnouncementPreviewCard.vue';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import { index } from '@/routes/announcements';
import type { AnnouncementPreview } from '@/types/announcement-preview';
defineProps<{ previews: AnnouncementPreview[] }>();
</script>
<template>
    <section
        v-if="previews.length"
        id="announcements"
        aria-labelledby="public-announcements-heading"
        class="border-border border-t"
    >
        <div
            class="mx-auto flex max-w-[1440px] flex-col gap-8 px-6 py-12 sm:px-10 lg:px-20"
        >
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="space-y-3">
                    <p
                        class="text-primary text-[13px] tracking-[0.12em] uppercase"
                    >
                        News from the association
                    </p>
                    <h2
                        id="public-announcements-heading"
                        class="font-serif text-4xl leading-tight font-normal"
                    >
                        Announcements
                    </h2>
                </div>
                <Link
                    :href="index()"
                    class="text-primary focus-visible:outline-primary rounded-sm text-sm font-medium underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4"
                    >View all announcements</Link
                >
            </div>
            <Carousel
                :opts="{ align: 'start', loop: false }"
                aria-label="Public announcements"
                class="focus-visible:ring-ring min-w-0 rounded-sm focus-visible:ring-2 focus-visible:ring-offset-4"
            >
                <CarouselContent class="-ml-6 touch-pan-y"
                    ><CarouselItem
                        v-for="preview in previews"
                        :key="preview.id"
                        class="pl-6 md:basis-1/2 lg:basis-1/3"
                        ><AnnouncementPreviewCard
                            :preview="preview" /></CarouselItem
                ></CarouselContent>
                <div
                    v-if="previews.length > 1"
                    class="mt-6 flex items-center justify-end gap-2"
                >
                    <CarouselPrevious
                        class="static size-10 translate-x-0 translate-y-0"
                    />
                    <CarouselNext
                        class="static size-10 translate-x-0 translate-y-0"
                    />
                </div>
            </Carousel>
        </div>
    </section>
</template>
