<script setup lang="ts">
import { Camera } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useEventQrScanner } from '@/composables/useEventQrScanner';
defineProps<{ disabled?: boolean }>();
const emit = defineEmits<{ scan: [ticket: string] }>();
const { video, active, starting, message, start, stop } = useEventQrScanner(
    (ticket) => emit('scan', ticket),
);
defineExpose({ stop });
</script>
<template>
    <section
        class="bg-card space-y-4 rounded-xl border p-5"
        aria-labelledby="scan-heading"
    >
        <h2 id="scan-heading" class="font-serif text-2xl">Scan a QR ticket</h2>
        <p class="text-muted-foreground text-sm">
            Point the camera at one attendee's QR ticket. A valid ticket is
            checked in immediately. The camera stops after each scan.
        </p>
        <video
            ref="video"
            v-show="active"
            autoplay
            muted
            playsinline
            class="bg-muted aspect-video w-full max-w-lg rounded-lg object-cover"
            aria-label="QR scanner camera preview"
        />
        <p v-if="message" role="status" class="bg-muted rounded-lg p-4 text-sm">
            {{ message }}
        </p>
        <p v-if="active" role="status" class="text-muted-foreground text-sm">
            Camera ready. Hold the QR ticket steady.
        </p>
        <Button
            v-if="active || starting"
            type="button"
            variant="outline"
            @click="stop"
            >{{ starting ? 'Cancel camera request' : 'Stop camera' }}</Button
        >
        <Button
            v-else
            type="button"
            variant="outline"
            :disabled="disabled"
            @click="start"
            ><Camera class="size-4" />Start camera</Button
        >
    </section>
</template>
