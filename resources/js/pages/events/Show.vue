<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import RichTextContent from '@/components/RichTextContent.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatEventDate } from '@/lib/eventDates';
import { index } from '@/routes/events';
import type { AssociationEvent } from '@/types/event';
defineOptions({ layout: [] });
defineProps<{ event: AssociationEvent }>();
</script>
<template>
    <div class="bg-background text-foreground min-h-screen">
        <Head :title="event.title" />
        <main class="mx-auto max-w-3xl space-y-8 px-6 py-12 sm:px-10">
            <Button variant="outline" size="sm" as-child
                ><Link :href="index()">Back to Events</Link></Button
            >
            <article class="space-y-8">
                <header class="space-y-5">
                    <div class="flex gap-2">
                        <Badge>Free Event</Badge
                        ><Badge variant="outline">{{
                            event.audience_label
                        }}</Badge>
                    </div>
                    <h1
                        class="font-serif text-4xl leading-tight break-words sm:text-5xl"
                    >
                        {{ event.title }}
                    </h1>
                    <img
                        v-if="event.image_url"
                        :src="event.image_url"
                        alt=""
                        class="bg-muted max-h-[70vh] w-full rounded-xl border object-contain"
                    />
                </header>
                <dl
                    class="bg-card grid gap-5 rounded-xl border p-6 sm:grid-cols-2"
                >
                    <div>
                        <dt class="text-muted-foreground text-sm">
                            Starts · Manila time
                        </dt>
                        <dd class="mt-1">
                            {{ formatEventDate(event.starts_at) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">
                            Ends · Manila time
                        </dt>
                        <dd class="mt-1">
                            {{ formatEventDate(event.ends_at) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">Venue</dt>
                        <dd class="mt-1 break-words">{{ event.venue }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">
                            Registration deadline · Manila time
                        </dt>
                        <dd class="mt-1">
                            {{ formatEventDate(event.registration_deadline) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">
                            Attendee capacity
                        </dt>
                        <dd class="mt-1">{{ event.capacity ?? 'No limit' }}</dd>
                    </div>
                </dl>
                <RichTextContent
                    :html="event.description"
                    class="text-base leading-8 break-words"
                />
            </article>
        </main>
    </div>
</template>
