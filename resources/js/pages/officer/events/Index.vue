<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatEventDate } from '@/lib/eventDates';
import { create, edit, index } from '@/routes/officer/events';
import type { AssociationEvent } from '@/types/event';
defineProps<{ events: AssociationEvent[] }>();
setLayoutProps({ breadcrumbs: [{ title: 'Events', href: index() }] });
</script>
<template>
    <div class="space-y-6 p-4 sm:p-6">
        <Head title="Manage Events" />
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Events"
                description="Create, publish, and manage free community gatherings."
            /><Button as-child><Link :href="create()">New Event</Link></Button>
        </div>
        <div
            v-if="events.length"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <Card v-for="event in events" :key="event.id"
                ><CardContent class="space-y-4"
                    ><div class="flex flex-wrap gap-2">
                        <Badge variant="outline">{{ event.status }}</Badge
                        ><Badge variant="secondary">{{
                            event.audience_label
                        }}</Badge>
                    </div>
                    <h2 class="font-serif text-2xl">{{ event.title }}</h2>
                    <p class="text-muted-foreground text-sm">
                        {{ formatEventDate(event.starts_at) }} · Manila time
                    </p>
                    <p class="text-muted-foreground text-sm">
                        {{ event.venue }}
                    </p>
                    <Button variant="outline" as-child
                        ><Link :href="edit(event.id)">Manage</Link></Button
                    ></CardContent
                ></Card
            >
        </div>
        <p v-else class="text-muted-foreground rounded-xl border p-8">
            No Events yet. Create your first draft.
        </p>
    </div>
</template>
