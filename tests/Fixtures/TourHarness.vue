<script setup lang="ts">
import { useSidebar } from '@/components/ui/sidebar';
import { useNavigationSection } from '@/composables/useNavigationSection';
import { useTour } from '@/composables/useTour';
import type { TourStep } from '@/composables/useTour';

const props = defineProps<{
    steps: TourStep[];
    onDismiss: () => void;
    onEnd: () => void;
}>();
const tour = useTour();
const sidebar = useSidebar();
const { openNavigationSection } = useNavigationSection();
openNavigationSection.value = 'membership';

function start(): Promise<void> {
    return tour.start(
        props.steps.map((step) =>
            step.target === 'navigation'
                ? { ...step, prepare: tour.revealNavigationSection(step.title) }
                : step,
        ),
        { onDismiss: props.onDismiss, onEnd: props.onEnd },
    );
}
</script>

<template>
    <div>
        <button data-action="start" @click="start()">Start</button>
        <button data-action="cancel" @click="tour.destroy()">Cancel</button>
        <button
            data-action="destination"
            @click="openNavigationSection = 'officer'"
        >
            Destination
        </button>
        <span data-state="running">{{ tour.isRunning.value }}</span>
        <span data-state="sidebar">{{ sidebar.open.value }}</span>
        <span data-state="section">{{ openNavigationSection }}</span>
    </div>
</template>
