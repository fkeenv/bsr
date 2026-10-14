<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatEventDate } from '@/lib/eventDates';
import { index, show } from '@/routes/officer/event-check-in';
import type { AssociationEvent } from '@/types/event';
defineProps<{ events: AssociationEvent[] }>();
setLayoutProps({ breadcrumbs: [{ title: 'Event check-in', href: index() }] });
</script>
<template>
    <div class="space-y-6 p-4 sm:p-6">
        <Head title="Event check-in" /><Heading
            title="Event check-in"
            description="Choose the Event before scanning tickets or finding attendees by name."
        />
        <div
            v-if="events.length"
            class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
        >
            <Card v-for="event in events" :key="event.id"
                ><CardContent class="space-y-4"
                    ><Badge variant="outline">{{ event.audience_label }}</Badge>
                    <h2 class="font-serif text-2xl">{{ event.title }}</h2>
                    <p class="text-muted-foreground text-sm">
                        {{ formatEventDate(event.starts_at) }} · Manila time
                    </p>
                    <p class="text-muted-foreground text-sm">
                        {{ event.venue }}
                    </p>
                    <Button as-child
                        ><Link :href="show(event.id)"
                            >Open check-in</Link
                        ></Button
                    ></CardContent
                ></Card
            >
        </div>
        <p v-else class="text-muted-foreground rounded-xl border p-6">
            There are no published Events available for check-in.
        </p>
    </div>
</template>
