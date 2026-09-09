<script lang="ts" setup>
import type { ToasterProps } from 'vue-sonner';
import {
    CircleCheckIcon,
    InfoIcon,
    Loader2Icon,
    OctagonXIcon,
    TriangleAlertIcon,
    XIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import { Toaster as Sonner } from 'vue-sonner';
import { cn } from '@/lib/utils';

import 'vue-sonner/style.css';

const props = withDefaults(defineProps<ToasterProps>(), {
    position: 'top-right',
});

const toastOptions = computed(() => ({
    ...props.toastOptions,
    classes: {
        toast: cn('border shadow-lg', props.toastOptions?.classes?.toast),
        title: props.toastOptions?.classes?.title,
        description: props.toastOptions?.classes?.description,
        loader: props.toastOptions?.classes?.loader,
        closeButton: props.toastOptions?.classes?.closeButton,
        cancelButton: props.toastOptions?.classes?.cancelButton,
        actionButton: props.toastOptions?.classes?.actionButton,
        content: props.toastOptions?.classes?.content,
        icon: props.toastOptions?.classes?.icon,
        loading: props.toastOptions?.classes?.loading,
        success: cn(
            '!border-green-200 !bg-green-50 !text-green-900',
            'dark:!border-green-800 dark:!bg-green-950 dark:!text-green-50',
            props.toastOptions?.classes?.success,
        ),
        error: cn(
            '!border-red-200 !bg-red-50 !text-red-900',
            'dark:!border-red-800 dark:!bg-red-950 dark:!text-red-50',
            props.toastOptions?.classes?.error,
        ),
        warning: cn(
            '!border-yellow-200 !bg-yellow-50 !text-yellow-950',
            'dark:!border-yellow-800 dark:!bg-yellow-950 dark:!text-yellow-50',
            props.toastOptions?.classes?.warning,
        ),
        info: cn(
            '!border-blue-200 !bg-blue-50 !text-blue-900',
            'dark:!border-blue-800 dark:!bg-blue-950 dark:!text-blue-50',
            props.toastOptions?.classes?.info,
        ),
        default: cn(
            '!border-zinc-200 !bg-zinc-50 !text-zinc-900',
            'dark:!border-zinc-700 dark:!bg-zinc-900 dark:!text-zinc-50',
            props.toastOptions?.classes?.default,
        ),
    },
}));
</script>

<template>
    <Sonner
        :class="cn('toaster group', props.class)"
        :style="{
            '--normal-bg': 'var(--popover)',
            '--normal-text': 'var(--popover-foreground)',
            '--normal-border': 'var(--border)',
            '--border-radius': 'var(--radius)',
        }"
        v-bind="props"
        :position="props.position"
        :toast-options="toastOptions"
    >
        <template #success-icon>
            <CircleCheckIcon class="size-4" />
        </template>
        <template #info-icon>
            <InfoIcon class="size-4" />
        </template>
        <template #warning-icon>
            <TriangleAlertIcon class="size-4" />
        </template>
        <template #error-icon>
            <OctagonXIcon class="size-4" />
        </template>
        <template #loading-icon>
            <div>
                <Loader2Icon class="size-4 animate-spin" />
            </div>
        </template>
        <template #close-icon>
            <XIcon class="size-4" />
        </template>
    </Sonner>
</template>
