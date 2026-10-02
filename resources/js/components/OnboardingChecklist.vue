<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CircleCheck, Circle, ClipboardCheck } from '@lucide/vue';
import { computed } from 'vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { OnboardingChecklistItem } from '@/types/onboarding';

const props = defineProps<{
    title: string;
    items: OnboardingChecklistItem[];
}>();

const completedCount = computed(
    () => props.items.filter((item) => item.isComplete).length,
);
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-start gap-3">
                <div
                    class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-md"
                >
                    <ClipboardCheck class="size-5" />
                </div>
                <div>
                    <CardTitle>{{ title }}</CardTitle>
                    <CardDescription>
                        {{ completedCount }} of {{ items.length }} done
                    </CardDescription>
                </div>
            </div>
        </CardHeader>
        <CardContent>
            <ul class="grid gap-2 sm:grid-cols-2">
                <li v-for="item in items" :key="item.key">
                    <Link
                        v-bind="item.link"
                        class="hover:bg-muted/40 focus-visible:ring-ring flex h-full w-full items-start gap-3 rounded-md border p-3 text-left focus-visible:ring-[3px] focus-visible:outline-none"
                    >
                        <CircleCheck
                            v-if="item.isComplete"
                            class="text-primary mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <Circle
                            v-else
                            class="text-muted-foreground mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <span class="grid gap-0.5 text-sm">
                            <span class="font-medium">
                                {{ item.title }}
                                <span class="sr-only">
                                    {{ item.isComplete ? '(done)' : '(to do)' }}
                                </span>
                            </span>
                            <span class="text-muted-foreground">
                                {{ item.description }}
                            </span>
                        </span>
                    </Link>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
