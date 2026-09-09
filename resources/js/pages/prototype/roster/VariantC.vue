<script setup lang="ts">
/**
 * Variant C — Split roster workspace + filters.
 * Left rail: filtered unpaid list (Block, Lot, this-period status, owes-for
 * Billing Period). Right pane: Property SoA drill-in. Default sort Balance ↓.
 * Word badges for this Billing Period status (not dots alone).
 */
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import PropertyDrillIn from './PropertyDrillIn.vue';
import {
    formatPhp,
    officerNav,
    sortByOutstandingDesc,
    thisBillingPeriodLabel,
    thisPeriodLabel,
    type PeriodStatus,
    type RosterProperty,
    type RosterScenario,
} from './data';

defineOptions({ name: 'RosterVariantC' });

const props = defineProps<{ data: RosterScenario }>();

const ALL = 'all';

const blockFilter = ref<string>(ALL);
const lotFilter = ref('');
const statusFilter = ref<string>(ALL);
/** Billing Period id the Property still owes on, or "opening", or all. */
const owesForFilter = ref<string>(ALL);

const blockOptions = computed(() => {
    const set = new Set(props.data.unpaid.map((r) => r.block));
    return [...set].sort((a, b) => a - b);
});

const owesForOptions = computed(() => {
    const periods = new Map<string, string>();
    let hasOpening = false;
    for (const row of props.data.unpaid) {
        if (row.openingBalanceRemaining > 0) {
            hasOpening = true;
        }
        for (const p of row.periods) {
            if (p.remaining > 0) {
                periods.set(p.id, p.label);
            }
        }
    }
    const list = [...periods.entries()]
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([id, label]) => ({ id, label }));
    return { hasOpening, periods: list };
});

const filtered = computed(() => {
    let list = props.data.unpaid;

    if (blockFilter.value !== ALL) {
        const block = Number(blockFilter.value);
        list = list.filter((r) => r.block === block);
    }

    const lotRaw = lotFilter.value.trim();
    if (lotRaw !== '') {
        const lot = Number(lotRaw);
        if (!Number.isNaN(lot)) {
            list = list.filter((r) => r.lot === lot);
        }
    }

    if (statusFilter.value !== ALL) {
        list = list.filter(
            (r) => r.thisPeriodStatus === (statusFilter.value as PeriodStatus),
        );
    }

    if (owesForFilter.value === 'opening') {
        list = list.filter((r) => r.openingBalanceRemaining > 0);
    } else if (owesForFilter.value !== ALL) {
        const periodId = owesForFilter.value;
        list = list.filter((r) =>
            r.periods.some((p) => p.id === periodId && p.remaining > 0),
        );
    }

    return sortByOutstandingDesc(list);
});

const filtersActive = computed(
    () =>
        blockFilter.value !== ALL ||
        lotFilter.value.trim() !== '' ||
        statusFilter.value !== ALL ||
        owesForFilter.value !== ALL,
);

const selectedId = ref<string | null>(filtered.value[0]?.id ?? null);

watch(
    filtered,
    (rows) => {
        if (!rows.find((r) => r.id === selectedId.value)) {
            selectedId.value = rows[0]?.id ?? null;
        }
    },
    { immediate: true },
);

const selected = computed(
    () => filtered.value.find((r) => r.id === selectedId.value) ?? null,
);

function clearFilters(): void {
    blockFilter.value = ALL;
    lotFilter.value = '';
    statusFilter.value = ALL;
    owesForFilter.value = ALL;
}

function periodTone(status: PeriodStatus): string {
    if (status === 'unpaid') {
        return 'bg-red-50 text-red-900';
    }
    if (status === 'partial') {
        return 'bg-amber-50 text-amber-950';
    }
    return 'bg-emerald-50 text-emerald-900';
}

function rowHint(row: RosterProperty): string {
    const bits: string[] = [];
    if (row.oldestOpenLabel) {
        bits.push(`since ${row.oldestOpenLabel}`);
    }
    if (row.recordedOwner) {
        bits.push(row.recordedOwner);
    }
    return bits.join(' · ');
}
</script>

<template>
    <div class="flex flex-col gap-3 pb-8">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <div>
                    <h1 class="text-xl font-semibold">Unpaid</h1>
                    <p class="text-muted-foreground text-xs">
                        {{ thisBillingPeriodLabel }} · Outstanding &gt; ₱0
                    </p>
                </div>
                <nav
                    class="flex flex-wrap gap-1 text-xs"
                    aria-label="Officer surfaces"
                >
                    <span
                        v-for="item in officerNav"
                        :key="item.key"
                        class="rounded-full border px-2.5 py-1"
                        :class="
                            item.active
                                ? 'border-zinc-900 bg-zinc-900 text-zinc-50'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ item.label }}
                    </span>
                </nav>
            </div>
            <Badge variant="outline">C · Split + filters</Badge>
        </header>

        <div
            v-if="data.unpaid.length === 0"
            class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-6 py-24 text-center"
        >
            <p class="text-lg font-semibold">Roster is clear</p>
            <p class="text-muted-foreground max-w-sm text-sm">
                {{ data.rosterCount }} Properties on the books — none with an
                Outstanding Balance right now.
            </p>
        </div>

        <template v-else>
            <div
                class="bg-muted/20 grid gap-3 rounded-xl border p-3 sm:grid-cols-2 lg:grid-cols-5"
            >
                <div class="space-y-1">
                    <Label class="text-xs">Block</Label>
                    <Select v-model="blockFilter">
                        <SelectTrigger class="w-full bg-background">
                            <SelectValue placeholder="All blocks" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL">All blocks</SelectItem>
                            <SelectItem
                                v-for="b in blockOptions"
                                :key="b"
                                :value="String(b)"
                            >
                                Block {{ b }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1">
                    <Label class="text-xs">Lot</Label>
                    <Input
                        v-model="lotFilter"
                        type="number"
                        min="1"
                        placeholder="Any"
                        class="bg-background"
                    />
                </div>

                <div class="space-y-1">
                    <Label class="text-xs"
                        >{{ thisBillingPeriodLabel }} status</Label
                    >
                    <Select v-model="statusFilter">
                        <SelectTrigger class="w-full bg-background">
                            <SelectValue placeholder="Any status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL">Any status</SelectItem>
                            <SelectItem value="unpaid">Unpaid</SelectItem>
                            <SelectItem value="partial">Partial</SelectItem>
                            <SelectItem value="paid"
                                >Paid (still owes older)</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1">
                    <Label class="text-xs">Owes for</Label>
                    <Select v-model="owesForFilter">
                        <SelectTrigger class="w-full bg-background">
                            <SelectValue placeholder="Any period" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL">Any open debt</SelectItem>
                            <SelectItem
                                v-if="owesForOptions.hasOpening"
                                value="opening"
                            >
                                Opening Balance
                            </SelectItem>
                            <SelectItem
                                v-for="p in owesForOptions.periods"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex items-end">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="w-full"
                        :disabled="!filtersActive"
                        @click="clearFilters"
                    >
                        Clear filters
                    </Button>
                </div>
            </div>

            <div
                class="grid min-h-[68vh] overflow-hidden rounded-xl border lg:grid-cols-[minmax(280px,360px)_1fr]"
            >
                <aside
                    class="bg-muted/30 flex max-h-[68vh] flex-col border-b lg:border-r lg:border-b-0"
                >
                    <div
                        class="text-muted-foreground flex items-center justify-between border-b px-3 py-2 text-xs"
                    >
                        <span>
                            {{ filtered.length }}
                            <template v-if="filtersActive">
                                of {{ data.unpaid.length }}
                            </template>
                            Properties
                        </span>
                        <span>Balance ↓</span>
                    </div>

                    <div
                        v-if="filtered.length === 0"
                        class="text-muted-foreground flex flex-1 flex-col items-center justify-center gap-2 px-4 py-12 text-center text-sm"
                    >
                        <p class="font-medium text-foreground">
                            No matches
                        </p>
                        <p>Nothing in the unpaid set fits these filters.</p>
                        <Button
                            type="button"
                            size="sm"
                            variant="secondary"
                            @click="clearFilters"
                        >
                            Clear filters
                        </Button>
                    </div>

                    <ul v-else class="flex-1 overflow-y-auto">
                        <li v-for="row in filtered" :key="row.id">
                            <button
                                type="button"
                                class="flex w-full flex-col gap-1.5 border-b px-3 py-3 text-left text-sm last:border-b-0"
                                :class="
                                    selectedId === row.id
                                        ? 'bg-background font-medium'
                                        : 'hover:bg-background/70'
                                "
                                @click="selectedId = row.id"
                            >
                                <span class="flex justify-between gap-2">
                                    <span class="truncate">{{
                                        row.propertyLabel
                                    }}</span>
                                    <span
                                        class="shrink-0 tabular-nums font-semibold"
                                    >
                                        {{
                                            formatPhp(row.outstandingBalance)
                                        }}
                                    </span>
                                </span>
                                <span class="flex flex-wrap items-center gap-1.5">
                                    <span
                                        class="inline-block rounded px-1.5 py-0.5 text-[10px] font-medium"
                                        :class="
                                            periodTone(row.thisPeriodStatus)
                                        "
                                    >
                                        {{ thisBillingPeriodLabel }}:
                                        {{
                                            thisPeriodLabel(
                                                row.thisPeriodStatus,
                                            )
                                        }}
                                    </span>
                                </span>
                                <span
                                    v-if="rowHint(row)"
                                    class="text-muted-foreground truncate text-xs font-normal"
                                >
                                    {{ rowHint(row) }}
                                </span>
                            </button>
                        </li>
                    </ul>
                </aside>

                <div class="min-h-[56vh] overflow-y-auto p-4">
                    <PropertyDrillIn
                        v-if="selected"
                        :property="selected"
                        :show-back="false"
                    />
                    <p
                        v-else
                        class="text-muted-foreground flex h-full items-center justify-center text-sm"
                    >
                        Select a Property from the list.
                    </p>
                </div>
            </div>
        </template>
    </div>
</template>
