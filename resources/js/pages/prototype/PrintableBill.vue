<script setup lang="ts">
/**
 * PROTOTYPE: three variants of the printable bill layout.
 * Switch via ?variant=A|B|C and ?scenario=current|behind|opening
 *
 * A = classic month letter · B = Outstanding-first + stub · C = Officer batch
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PrototypeSwitcher from '@/components/prototype/PrototypeSwitcher.vue';
import VariantA from '@/pages/prototype/bill/VariantA.vue';
import VariantB from '@/pages/prototype/bill/VariantB.vue';
import VariantC from '@/pages/prototype/bill/VariantC.vue';
import {
    getScenario,
    scenarioMeta,
    type ScenarioKey,
} from '@/pages/prototype/bill/data';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Prototype · Printable bill',
                href: '/prototype/printable-bill',
            },
        ],
    },
});

const variants = [
    { key: 'A', name: 'Classic month letter' },
    { key: 'B', name: 'Outstanding + stub' },
    { key: 'C', name: 'Officer batch run' },
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
    const s = query.value.get('scenario') ?? 'behind';

    return (
        ['current', 'behind', 'opening'].includes(s) ? s : 'behind'
    ) as ScenarioKey;
});

const bill = computed(() => getScenario(scenario.value));

const issuedOn = '9 September 2026';
</script>

<template>
    <Head title="Prototype · Printable bill" />

    <div class="flex flex-1 flex-col gap-4 bg-zinc-200/80 p-4 pb-36 print:bg-white print:p-0">
        <div
            class="rounded-lg border border-dashed border-amber-400/80 bg-amber-50/90 px-3 py-2 text-sm text-amber-950 print:hidden"
        >
            <strong>Throwaway prototype.</strong>
            Printable bill layouts (A4 preview). Bottom bar flips variants and
            scenarios. Letterhead and payment account numbers are placeholders.
            Scenario:
            <em>{{ scenarioMeta[scenario].blurb }}</em>
        </div>

        <VariantA
            v-if="variant === 'A'"
            :key="`A-${scenario}`"
            :bill="bill"
            :issued-on="issuedOn"
        />
        <VariantB
            v-else-if="variant === 'B'"
            :key="`B-${scenario}`"
            :bill="bill"
            :issued-on="issuedOn"
        />
        <VariantC
            v-else
            :key="`C-${scenario}`"
            :bill="bill"
            :scenario="scenario"
            :issued-on="issuedOn"
        />

        <PrototypeSwitcher
            :variants="[...variants]"
            :current="variant"
            :scenarios="scenarios"
            :scenario="scenario"
        />
    </div>
</template>
