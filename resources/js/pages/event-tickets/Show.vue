<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EventTicketStatus from '@/components/EventTicketStatus.vue';
import { Button } from '@/components/ui/button';
import EventScheduleNotice from '@/components/EventScheduleNotice.vue';
import { formatEventDate } from '@/lib/eventDates';
import type { AssociationEvent } from '@/types/event';
import { show } from '@/routes/event-registrations';
import type { EventTicket } from '@/types/event-registration';
defineOptions({ layout: [] });
defineProps<{
    ticket: EventTicket;
    event: AssociationEvent;
    registrationId: number;
}>();
function printTicket(): void {
    window.print();
}
</script>
<template>
    <div class="bg-background text-foreground min-h-screen px-6 py-12">
        <Head :title="`Ticket · ${event.title}`" />
        <main class="mx-auto max-w-md space-y-6 rounded-xl border p-6 sm:p-8">
            <Button variant="ghost" as-child class="print:hidden"
                ><Link :href="show(registrationId)"
                    >Back to registration</Link
                ></Button
            >
            <p class="text-muted-foreground text-sm">
                Blessed Sacrament Residences · Event ticket
            </p>
            <h1 class="font-serif text-3xl break-words">{{ event.title }}</h1>
            <p class="text-muted-foreground text-sm">
                {{ formatEventDate(event.starts_at) }} · Manila time<br />{{
                    event.venue
                }}
            </p>
            <EventScheduleNotice :event="event" />
            <h2 class="text-xl font-medium break-words">{{ ticket.name }}</h2>
            <EventTicketStatus :ticket="ticket" :event="event" />
            <img
                v-if="ticket.qr_url"
                :src="ticket.qr_url"
                :alt="`QR ticket for ${ticket.name}`"
                width="256"
                height="256"
                class="mx-auto aspect-square max-w-full rounded-lg bg-white"
            />
            <p v-else class="text-destructive">
                This ticket cannot be used for admission.
            </p>
            <Button
                v-if="ticket.valid"
                type="button"
                class="print:hidden"
                @click="printTicket"
                >Print ticket</Button
            >
        </main>
    </div>
</template>
