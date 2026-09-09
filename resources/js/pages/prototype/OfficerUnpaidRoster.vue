<script setup lang="ts">
/**
 * PROTOTYPE: three variants of the Officer unpaid roster.
 * Switch via ?variant=A|B|C and ?scenario=mixed|clear|crowded
 *
 * A = dense scan table · B = severity groups · C = split roster workspace
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PrototypeSwitcher from '@/components/prototype/PrototypeSwitcher.vue';
import {
    getScenario,
    scenarioMeta,
    type ScenarioKey,
} from '@/pages/prototype/roster/data';
import VariantA from '@/pages/prototype/roster/VariantA.vue';
import VariantB from '@/pages/prototype/roster/VariantB.vue';
import VariantC from '@/pages/prototype/roster/VariantC.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Prototype · Officer unpaid roster',
                href: '/prototype/officer-unpaid-roster',
            },
        ],
    },
});

const variants = [
    { key: 'A', name: 'Dense table' },
    { key: 'B', name: 'Severity groups' },
    { key: 'C', name: 'Split workspace' },
] as const;

const scenarios = (Object.keys(scenarioMeta) as ScenarioKey[]).map((key) => ({
    key,
    label: scenarioMeta[key].label,
}));

const page = usePage();

const query = computed(() => {
    const url = new URL(
        page.url,
        typeof window !== 'undefined'
            ? window.location.origin
            : 'http://localhost',
    );

    return url.searchParams;
});

const variant = computed(() => {
    const v = query.value.get('variant') ?? 'A';

    return ['A', 'B', 'C'].includes(v) ? v : 'A';
});

const scenario = computed<ScenarioKey>(() => {
    const s = query.value.get('scenario') ?? 'mixed';

    return (
        ['mixed', 'clear', 'crowded'].includes(s) ? s : 'mixed'
    ) as ScenarioKey;
});

const data = computed(() => getScenario(scenario.value));
</script>

<template>
    <Head title="Prototype · Officer unpaid roster" />

    <div class="flex flex-1 flex-col gap-4 p-4 pb-36">
        <div
            class="rounded-lg border border-dashed border-amber-400/80 bg-amber-50/70 px-3 py-2 text-sm text-amber-950"
        >
            <strong>Throwaway prototype.</strong>
            Not production. Bottom bar flips variants and scenarios. Scenario:
            <em>{{ scenarioMeta[scenario].blurb }}</em>
        </div>

        <VariantA
            v-if="variant === 'A'"
            :key="`A-${scenario}`"
            :data="data"
        />
        <VariantB
            v-else-if="variant === 'B'"
            :key="`B-${scenario}`"
            :data="data"
        />
        <VariantC v-else :key="`C-${scenario}`" :data="data" />

        <PrototypeSwitcher
            :variants="[...variants]"
            :current="variant"
            :scenarios="scenarios"
            :scenario="scenario"
        />
    </div>
</template>
