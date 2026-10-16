<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { formatEventDate } from '@/lib/eventDates';
import type { AssociationEvent } from '@/types/event';
defineProps<{ event: AssociationEvent }>();
</script>
<template>
    <Alert v-if="event.status === 'rescheduled'">
        <AlertTitle>Rescheduled Event</AlertTitle>
        <AlertDescription class="space-y-2">
            <p>
                New schedule: {{ formatEventDate(event.starts_at) }} to
                {{ formatEventDate(event.ends_at) }} · Manila time.
            </p>
            <p>
                Registration closes
                {{ formatEventDate(event.registration_deadline) }} · Manila
                time.
            </p>
            <p>
                Existing active tickets remain valid. Unpaid reservations keep
                their original expiry.
            </p>
        </AlertDescription>
    </Alert>
</template>
