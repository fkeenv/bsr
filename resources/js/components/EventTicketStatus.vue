<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { presentEventTicket } from '@/lib/eventPresentation';
import type { AssociationEvent } from '@/types/event';
import type {
    EventRegistration,
    EventTicket,
} from '@/types/event-registration';

const props = defineProps<{
    ticket: EventTicket;
    event: AssociationEvent;
    registrationStatus?: EventRegistration['status'];
}>();
const presentation = computed(() =>
    presentEventTicket(props.ticket, props.event, props.registrationStatus),
);
</script>

<template>
    <div class="space-y-2">
        <Badge variant="outline">{{ presentation.label }}</Badge>
        <p
            v-if="presentation.description"
            class="text-destructive text-sm font-medium"
        >
            {{ presentation.description }}
        </p>
        <p v-if="presentation.history" class="text-muted-foreground text-sm">
            {{ presentation.history }}
        </p>
    </div>
</template>
