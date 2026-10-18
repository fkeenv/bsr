<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ContentImage from '@/components/ContentImage.vue';
import { Card, CardContent } from '@/components/ui/card';
import { show } from '@/routes/announcements';
import type { AnnouncementPreview } from '@/types/announcement-preview';
const props = defineProps<{ preview: AnnouncementPreview }>();
const stockImage = '/images/announcements/community-stock.webp';
</script>
<template>
    <Card class="h-full overflow-hidden py-0">
        <ContentImage
            :src="props.preview.image_url"
            :fallback-src="stockImage"
            alt=""
        >
            <template #fallback
                ><div aria-hidden="true" class="bg-muted aspect-video w-full"
            /></template>
        </ContentImage>
        <CardContent class="flex flex-1 flex-col gap-3 pb-6">
            <time
                :datetime="preview.published_at"
                class="text-muted-foreground text-sm"
                >{{
                    new Date(preview.published_at).toLocaleDateString('en-PH', {
                        timeZone: 'Asia/Manila',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                    })
                }}</time
            >
            <h3 class="font-serif text-2xl leading-tight">
                {{ preview.title }}
            </h3>
            <p class="text-muted-foreground text-sm leading-6">
                {{ preview.excerpt }}
            </p>
            <Link
                :href="show(preview.id)"
                class="text-primary focus-visible:outline-primary mt-auto w-fit rounded-sm pt-2 text-sm font-medium underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4"
                :aria-label="`See more: ${preview.title}`"
                >See more</Link
            >
        </CardContent>
    </Card>
</template>
