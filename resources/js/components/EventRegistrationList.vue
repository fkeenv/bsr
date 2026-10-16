<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatEventDate } from '@/lib/eventDates';
import { show } from '@/routes/event-registrations';
import type { EventRegistration } from '@/types/event-registration';
defineProps<{ registrations: EventRegistration[] }>();
</script>
<template>
    <div class="space-y-4">
        <div
            v-if="registrations.length"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <Card v-for="registration in registrations" :key="registration.id"
                ><CardContent class="space-y-4">
                    <Badge
                        :variant="
                            registration.cancelled_at ||
                            registration.event.status === 'cancelled'
                                ? 'outline'
                                : 'secondary'
                        "
                        >{{
                            registration.event.status === 'cancelled'
                                ? 'Event cancelled'
                                : registration.status_label
                        }}</Badge
                    >
                    <Badge
                        v-if="registration.event.status === 'rescheduled'"
                        variant="outline"
                        >Rescheduled</Badge
                    >
                    <h2 class="font-serif text-2xl">
                        {{ registration.event.title }}
                    </h2>
                    <p class="text-muted-foreground text-sm">
                        {{ formatEventDate(registration.event.starts_at) }} ·
                        Manila time
                    </p>
                    <p class="text-sm">
                        {{ registration.attendee_count }} attendees ·
                        {{ registration.account_name }}
                    </p>
                    <Button variant="outline" as-child
                        ><Link :href="show(registration.id)"
                            >View registration</Link
                        ></Button
                    >
                </CardContent></Card
            >
        </div>
        <p v-else class="text-muted-foreground rounded-xl border p-8">
            No Event registrations yet.
        </p>
    </div>
</template>
