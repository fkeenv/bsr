<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays } from '@lucide/vue';
import ContentImage from '@/components/ContentImage.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { formatEventMoney } from '@/lib/eventMoney';
import { formatEventDate } from '@/lib/eventDates';
import { home } from '@/routes';
import { show } from '@/routes/events';
import type { AssociationEvent } from '@/types/event';
defineOptions({ layout: [] });
defineProps<{ events: AssociationEvent[] }>();
</script>
<template>
    <div class="bg-background text-foreground min-h-screen">
        <Head title="Community Events" />
        <header class="border-border border-b px-6 py-6">
            <Link
                :href="home()"
                class="font-serif text-xl underline-offset-4 hover:underline"
                >Blessed Sacrament Residences</Link
            >
        </header>
        <main class="mx-auto max-w-6xl space-y-8 px-6 py-12 sm:px-10">
            <div class="space-y-3">
                <h1 class="font-serif text-4xl sm:text-5xl">
                    Community Events
                </h1>
                <p class="text-muted-foreground">
                    Gatherings and activities from the association. All times
                    are in Manila time.
                </p>
            </div>
            <div
                v-if="events.length"
                class="grid gap-6 md:grid-cols-2 lg:grid-cols-3"
            >
                <Card
                    v-for="event in events"
                    :key="event.id"
                    class="overflow-hidden py-0"
                >
                    <ContentImage :src="event.image_url" alt="">
                        <template #fallback>
                            <div
                                data-slot="event-image-placeholder"
                                aria-hidden="true"
                                class="bg-muted text-muted-foreground grid aspect-video w-full place-items-center"
                            >
                                <CalendarDays class="size-10 opacity-40" />
                            </div>
                        </template>
                    </ContentImage>
                    <CardContent class="flex flex-1 flex-col gap-4 py-6">
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-if="event.status === 'rescheduled'"
                                variant="outline"
                                >Rescheduled</Badge
                            >
                            <Badge variant="secondary">{{
                                event.price_centavos > 0
                                    ? formatEventMoney(event.price_centavos)
                                    : 'Free'
                            }}</Badge
                            ><Badge variant="outline">{{
                                event.audience_label
                            }}</Badge>
                        </div>
                        <h2 class="font-serif text-2xl">{{ event.title }}</h2>
                        <p class="text-muted-foreground text-sm">
                            {{ formatEventDate(event.starts_at) }} ·
                            {{ event.venue }}
                        </p>
                        <p class="text-muted-foreground text-sm leading-6">
                            {{ event.excerpt }}
                        </p>
                        <Link
                            :href="show(event.id)"
                            class="text-primary mt-auto w-fit rounded-sm font-medium underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4"
                            :aria-label="`View Event: ${event.title}`"
                            >View Event</Link
                        >
                    </CardContent>
                </Card>
            </div>
            <p v-else class="text-muted-foreground rounded-xl border p-8">
                There are no Events available to you yet. Check back for
                community gatherings.
            </p>
        </main>
    </div>
</template>
