<script setup lang="ts">
/**
 * PROTOTYPE chrome — not part of the product UI.
 * Cycles ?variant= and optional ?scenario= via the Inertia router.
 */
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';

type VariantDef = { key: string; name: string };
type ScenarioDef = { key: string; label: string };

const props = defineProps<{
    variants: VariantDef[];
    current: string;
    scenarios?: ScenarioDef[];
    scenario?: string;
}>();

function setParams(next: { variant?: string; scenario?: string }): void {
    const url = new URL(window.location.href);

    if (next.variant !== undefined) {
        url.searchParams.set('variant', next.variant);
    }

    if (next.scenario !== undefined) {
        url.searchParams.set('scenario', next.scenario);
    }

    router.get(
        url.pathname + url.search,
        {},
        { replace: true, preserveState: true, preserveScroll: true },
    );
}

function cycle(delta: number): void {
    const keys = props.variants.map((v) => v.key);
    const idx = Math.max(0, keys.indexOf(props.current));
    const next = keys[(idx + delta + keys.length) % keys.length];
    setParams({ variant: next });
}

function onKey(e: KeyboardEvent): void {
    const target = e.target as HTMLElement | null;
    if (
        target &&
        (target.tagName === 'INPUT' ||
            target.tagName === 'TEXTAREA' ||
            target.isContentEditable)
    ) {
        return;
    }

    if (e.key === 'ArrowLeft') {
        cycle(-1);
    }

    if (e.key === 'ArrowRight') {
        cycle(1);
    }
}

onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));

const currentName = computed(
    () =>
        props.variants.find((v) => v.key === props.current)?.name ??
        props.current,
);

const isDev = import.meta.env.DEV;
</script>

<template>
    <div
        v-if="isDev"
        class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-center px-3"
    >
        <div
            class="pointer-events-auto flex max-w-full flex-col gap-2 rounded-2xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-zinc-50 shadow-2xl"
        >
            <div class="flex items-center gap-2">
                <Button
                    type="button"
                    size="icon-sm"
                    variant="secondary"
                    class="shrink-0"
                    aria-label="Previous variant"
                    @click="cycle(-1)"
                >
                    ←
                </Button>
                <div class="min-w-0 flex-1 text-center text-sm font-medium">
                    {{ current }} ({{ currentName }})
                </div>
                <Button
                    type="button"
                    size="icon-sm"
                    variant="secondary"
                    class="shrink-0"
                    aria-label="Next variant"
                    @click="cycle(1)"
                >
                    →
                </Button>
            </div>
            <div
                v-if="scenarios?.length"
                class="flex flex-wrap items-center justify-center gap-1 border-t border-zinc-800 pt-2"
            >
                <span class="mr-1 text-[10px] uppercase tracking-wide text-zinc-400"
                    >Scenario</span
                >
                <Button
                    v-for="s in scenarios"
                    :key="s.key"
                    type="button"
                    size="sm"
                    :variant="scenario === s.key ? 'default' : 'outline'"
                    class="h-7 text-xs"
                    @click="setParams({ scenario: s.key })"
                >
                    {{ s.label }}
                </Button>
            </div>
        </div>
    </div>
</template>
