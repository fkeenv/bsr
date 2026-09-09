<script setup lang="ts">
/**
 * Variant A — Dense scan table.
 * Classic Treasurer roster: Block+Lot, Outstanding Balance, this Billing Period,
 * oldest open. Default sort Outstanding desc. Row → full-page Property drill-in.
 */
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PropertyDrillIn from './PropertyDrillIn.vue';
import {
    formatPhp,
    officerNav,
    sortByBlockLot,
    sortByOutstandingDesc,
    thisBillingPeriodLabel,
    thisPeriodLabel,
    type RosterProperty,
    type RosterScenario,
} from './data';

defineOptions({ name: 'RosterVariantA' });

const props = defineProps<{ data: RosterScenario }>();

type SortKey = 'outstanding' | 'blockLot';

const sortKey = ref<SortKey>('outstanding');
const selectedId = ref<string | null>(null);

const rows = computed(() => {
    const list = props.data.unpaid;
    return sortKey.value === 'outstanding'
        ? sortByOutstandingDesc(list)
        : sortByBlockLot(list);
});

const selected = computed(
    () => rows.value.find((r) => r.id === selectedId.value) ?? null,
);

function openRow(row: RosterProperty): void {
    selectedId.value = row.id;
}

function periodTone(status: RosterProperty['thisPeriodStatus']): string {
    if (status === 'unpaid') {
        return 'bg-red-50 text-red-900';
    }
    if (status === 'partial') {
        return 'bg-amber-50 text-amber-950';
    }
    return 'bg-emerald-50 text-emerald-900';
}
</script>

<template>
    <div class="flex flex-col gap-4 pb-8">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-muted-foreground text-xs tracking-wide uppercase">
                    Officer · thin set
                </p>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Unpaid roster
                </h1>
                <p class="text-muted-foreground mt-1 max-w-xl text-sm">
                    Properties with Outstanding Balance &gt; ₱0 for
                    {{ thisBillingPeriodLabel }}. Dense table for a fast scan.
                </p>
            </div>
            <Badge variant="outline">A · Dense table</Badge>
        </header>

        <nav
            class="flex flex-wrap gap-1 border-b pb-2 text-sm"
            aria-label="Officer surfaces"
        >
            <span
                v-for="item in officerNav"
                :key="item.key"
                class="rounded-md px-3 py-1.5"
                :class="
                    item.active
                        ? 'bg-zinc-900 text-zinc-50'
                        : 'text-muted-foreground'
                "
            >
                {{ item.label }}
            </span>
        </nav>

        <PropertyDrillIn
            v-if="selected"
            :property="selected"
            show-back
            @back="selectedId = null"
        />

        <template v-else>
            <div
                v-if="rows.length === 0"
                class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-6 py-20 text-center"
            >
                <p class="text-lg font-semibold">All clear</p>
                <p class="text-muted-foreground max-w-md text-sm">
                    No Property has an Outstanding Balance. All
                    {{ data.rosterCount }} roster houses are at ₱0 for now.
                </p>
            </div>

            <template v-else>
                <div
                    class="flex flex-wrap items-center justify-between gap-2 text-sm"
                >
                    <p class="text-muted-foreground">
                        {{ rows.length }} unpaid · default sort Outstanding
                        Balance ↓
                    </p>
                    <div class="flex gap-1">
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                sortKey === 'outstanding' ? 'default' : 'outline'
                            "
                            @click="sortKey = 'outstanding'"
                        >
                            By balance
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                sortKey === 'blockLot' ? 'default' : 'outline'
                            "
                            @click="sortKey = 'blockLot'"
                        >
                            By Block+Lot
                        </Button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border">
                    <table class="w-full min-w-[640px] text-sm">
                        <thead class="bg-muted/50 text-left text-xs">
                            <tr>
                                <th class="px-3 py-2 font-medium">
                                    Block + Lot
                                </th>
                                <th class="px-3 py-2 font-medium">
                                    Recorded owner
                                </th>
                                <th
                                    class="px-3 py-2 text-right font-medium"
                                >
                                    Outstanding
                                </th>
                                <th class="px-3 py-2 font-medium">
                                    {{ thisBillingPeriodLabel }}?
                                </th>
                                <th class="px-3 py-2 font-medium">
                                    Oldest open
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in rows"
                                :key="row.id"
                                class="cursor-pointer border-t hover:bg-muted/40"
                                @click="openRow(row)"
                            >
                                <td class="px-3 py-2.5 font-medium">
                                    {{ row.propertyLabel }}
                                </td>
                                <td
                                    class="text-muted-foreground px-3 py-2.5"
                                >
                                    {{ row.recordedOwner ?? '—' }}
                                </td>
                                <td
                                    class="px-3 py-2.5 text-right tabular-nums font-semibold"
                                >
                                    {{ formatPhp(row.outstandingBalance) }}
                                </td>
                                <td class="px-3 py-2.5">
                                    <span
                                        class="inline-block rounded px-2 py-0.5 text-xs font-medium"
                                        :class="periodTone(row.thisPeriodStatus)"
                                    >
                                        {{
                                            thisPeriodLabel(
                                                row.thisPeriodStatus,
                                            )
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="text-muted-foreground px-3 py-2.5"
                                >
                                    {{ row.oldestOpenLabel ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </template>
    </div>
</template>
