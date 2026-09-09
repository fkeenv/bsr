<script setup lang="ts">
/**
 * Variant B — Severity groups.
 * Bucket Properties by shape (this month / months behind / opening only /
 * older only), not a flat table. Urgency hierarchy first; drill-in full page.
 */
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PropertyDrillIn from './PropertyDrillIn.vue';
import {
    formatPhp,
    officerNav,
    severityBucket,
    severityMeta,
    sortByOutstandingDesc,
    thisBillingPeriodLabel,
    thisPeriodLabel,
    type RosterProperty,
    type RosterScenario,
    type SeverityBucket,
} from './data';

defineOptions({ name: 'RosterVariantB' });

const props = defineProps<{ data: RosterScenario }>();

const selectedId = ref<string | null>(null);

const bucketOrder: SeverityBucket[] = [
    'behind',
    'this_month',
    'older_only',
    'opening_only',
];

const groups = computed(() => {
    const map = new Map<SeverityBucket, RosterProperty[]>();
    for (const key of bucketOrder) {
        map.set(key, []);
    }
    for (const row of sortByOutstandingDesc(props.data.unpaid)) {
        const bucket = severityBucket(row);
        map.get(bucket)!.push(row);
    }
    return bucketOrder
        .map((key) => ({
            key,
            ...severityMeta[key],
            rows: map.get(key)!,
        }))
        .filter((g) => g.rows.length > 0);
});

const selected = computed(
    () =>
        props.data.unpaid.find((r) => r.id === selectedId.value) ?? null,
);

function openRow(row: RosterProperty): void {
    selectedId.value = row.id;
}
</script>

<template>
    <div class="flex flex-col gap-4 pb-8">
        <div class="grid gap-4 lg:grid-cols-[200px_1fr]">
            <aside
                class="rounded-xl border bg-zinc-950 p-4 text-zinc-50 lg:min-h-[70vh]"
            >
                <p class="text-[10px] tracking-[0.2em] text-zinc-400 uppercase">
                    Officer
                </p>
                <p class="mt-1 font-semibold">BSR desk</p>
                <nav class="mt-6 flex flex-col gap-1 text-sm">
                    <span
                        v-for="item in officerNav"
                        :key="item.key"
                        class="rounded-md px-2 py-2"
                        :class="
                            item.active
                                ? 'bg-zinc-100 font-medium text-zinc-950'
                                : 'text-zinc-400'
                        "
                    >
                        {{ item.label }}
                    </span>
                </nav>
                <p class="mt-8 text-xs text-zinc-500">
                    Unpaid is one thin surface beside Announcements,
                    Applications, Payments, and Levy — not a reporting suite.
                </p>
            </aside>

            <div class="min-w-0">
                <header
                    class="mb-4 flex flex-wrap items-end justify-between gap-3"
                >
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">
                            Unpaid by shape
                        </h1>
                        <p class="text-muted-foreground mt-1 max-w-xl text-sm">
                            Grouped for
                            {{ thisBillingPeriodLabel }} — scan urgency, not
                            Block order.
                        </p>
                    </div>
                    <Badge variant="outline">B · Severity groups</Badge>
                </header>

                <PropertyDrillIn
                    v-if="selected"
                    :property="selected"
                    show-back
                    @back="selectedId = null"
                />

                <div
                    v-else-if="data.unpaid.length === 0"
                    class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-6 py-20 text-center"
                >
                    <p class="text-lg font-semibold">Nothing to collect</p>
                    <p class="text-muted-foreground max-w-md text-sm">
                        All {{ data.rosterCount }} Properties are at ₱0. Come
                        back after the next levy if anything stays open.
                    </p>
                </div>

                <div v-else class="flex flex-col gap-6">
                    <section
                        v-for="group in groups"
                        :key="group.key"
                        class="flex flex-col gap-2"
                    >
                        <div class="flex flex-wrap items-baseline gap-2">
                            <h2 class="text-sm font-semibold tracking-wide uppercase">
                                {{ group.title }}
                            </h2>
                            <span class="text-muted-foreground text-xs">
                                {{ group.rows.length }} · {{ group.hint }}
                            </span>
                        </div>
                        <ul class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            <li
                                v-for="row in group.rows"
                                :key="row.id"
                            >
                                <button
                                    type="button"
                                    class="hover:border-foreground/30 flex h-full w-full flex-col gap-2 rounded-lg border bg-background p-3 text-left transition-colors"
                                    @click="openRow(row)"
                                >
                                    <div
                                        class="flex items-start justify-between gap-2"
                                    >
                                        <span class="font-semibold">
                                            {{ row.propertyLabel }}
                                        </span>
                                        <span
                                            class="tabular-nums text-sm font-semibold"
                                        >
                                            {{
                                                formatPhp(
                                                    row.outstandingBalance,
                                                )
                                            }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-muted-foreground truncate text-xs"
                                    >
                                        {{
                                            row.recordedOwner ??
                                            'No recorded owner'
                                        }}
                                    </p>
                                    <div
                                        class="mt-auto flex flex-wrap gap-1 pt-1"
                                    >
                                        <Badge
                                            variant="secondary"
                                            class="text-[10px]"
                                        >
                                            {{ thisBillingPeriodLabel }}:
                                            {{
                                                thisPeriodLabel(
                                                    row.thisPeriodStatus,
                                                )
                                            }}
                                        </Badge>
                                        <Badge
                                            v-if="
                                                row.openingBalanceRemaining > 0
                                            "
                                            variant="outline"
                                            class="text-[10px]"
                                        >
                                            Opening
                                            {{
                                                formatPhp(
                                                    row.openingBalanceRemaining,
                                                )
                                            }}
                                        </Badge>
                                    </div>
                                </button>
                            </li>
                        </ul>
                    </section>

                    <p class="text-muted-foreground text-xs">
                        Print batch (Printed Bill) stays on the Levy / print
                        flow — not duplicated here.
                    </p>
                    <Button type="button" variant="outline" size="sm" disabled>
                        Print unpaid bills (stub)
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
