<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
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
import PropertyStatementPanel from '@/pages/officer/unpaid/PropertyStatementPanel.vue';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as unpaidIndex } from '@/routes/officer/unpaid';
import type { StatementOfAccountPage } from '@/types/statement-of-account';
import type { UnpaidRosterPage } from '@/types/unpaid-roster';

const props = defineProps<UnpaidRosterPage>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
            {
                title: 'Unpaid',
                href: unpaidIndex(),
            },
        ],
    },
});

const ALL = 'all';

const blockFilter = computed(() => props.values.block ?? ALL);
const lotFilter = computed(() => props.values.lot ?? '');
const statusFilter = computed(() => props.values.status ?? ALL);
const owesForFilter = computed(() => props.values.owes_for ?? ALL);

function formatPhp(value: string | number): string {
    const amountValue =
        typeof value === 'number' ? value : Number.parseFloat(value);

    return `₱${Number.isFinite(amountValue) ? amountValue.toFixed(2) : '0.00'}`;
}

function statusLabel(status: string): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}

function periodTone(status: string): string {
    if (status === 'unpaid') {
        return 'bg-red-50 text-red-900 dark:bg-red-950/40 dark:text-red-50';
    }

    if (status === 'partial') {
        return 'bg-amber-50 text-amber-950 dark:bg-amber-950/40 dark:text-amber-50';
    }

    return 'bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-50';
}

function visit(overrides: Record<string, string | number | null>): void {
    const query: Record<string, string | number> = {};

    const next = {
        block: props.values.block,
        lot: props.values.lot,
        status: props.values.status,
        owes_for: props.values.owes_for,
        property: props.values.property,
        charge: props.values.charge,
        ...overrides,
    };

    for (const [key, value] of Object.entries(next)) {
        if (
            value === null ||
            value === undefined ||
            value === '' ||
            value === ALL
        ) {
            continue;
        }

        query[key] = value;
    }

    router.get(
        unpaidIndex.url({ query }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function clearFilters(): void {
    visit({
        block: null,
        lot: null,
        status: null,
        owes_for: null,
        property: props.values.property,
        charge: props.values.charge,
    });
}

function selectProperty(propertyId: number): void {
    visit({ property: propertyId, charge: null });
}

function selectPeriod(chargeId: number): void {
    if (props.values.property === null) {
        return;
    }

    visit({ property: props.values.property, charge: chargeId });
}

function rowHint(row: UnpaidRosterPage['rows'][number]): string {
    const bits: string[] = [];

    if (row.oldest_open_label) {
        bits.push(`since ${row.oldest_open_label}`);
    }

    if (row.recorded_owner_name) {
        bits.push(row.recorded_owner_name);
    }

    return bits.join(' · ');
}

const selectedStatement = computed(
    (): StatementOfAccountPage | null => props.selected,
);
</script>

<template>
    <Head title="Unpaid" />

    <div class="flex flex-col gap-3 p-4 pb-8">
        <Heading
            title="Unpaid"
            :description="`${this_billing_period.label} · Outstanding > ₱0`"
        />

        <div
            v-if="empty_state === 'clear'"
            class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-6 py-24 text-center"
        >
            <p class="text-lg font-semibold">Roster is clear</p>
            <p class="text-muted-foreground max-w-sm text-sm">
                {{ roster_count }} Properties on the books — none with an
                Outstanding Balance right now.
            </p>
        </div>

        <template v-else>
            <div
                class="bg-muted/20 grid gap-3 rounded-xl border p-3 sm:grid-cols-2 lg:grid-cols-5"
            >
                <div class="space-y-1">
                    <Label class="text-xs">Block</Label>
                    <Select
                        :model-value="blockFilter"
                        @update:model-value="
                            (value) =>
                                visit({
                                    block: value === ALL ? null : String(value),
                                })
                        "
                    >
                        <SelectTrigger class="bg-background w-full">
                            <SelectValue placeholder="All blocks" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL">All blocks</SelectItem>
                            <SelectItem
                                v-for="block in filter_options.blocks"
                                :key="block"
                                :value="block"
                            >
                                Block {{ block }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1">
                    <Label class="text-xs">Lot</Label>
                    <Input
                        :model-value="lotFilter"
                        type="text"
                        inputmode="numeric"
                        placeholder="Any"
                        class="bg-background"
                        @change="
                            (event: Event) =>
                                visit({
                                    lot:
                                        (
                                            event.target as HTMLInputElement
                                        ).value.trim() || null,
                                })
                        "
                    />
                </div>

                <div class="space-y-1">
                    <Label class="text-xs"
                        >{{ this_billing_period.label }} status</Label
                    >
                    <Select
                        :model-value="statusFilter"
                        @update:model-value="
                            (value) =>
                                visit({
                                    status:
                                        value === ALL ? null : String(value),
                                })
                        "
                    >
                        <SelectTrigger class="bg-background w-full">
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
                    <Select
                        :model-value="owesForFilter"
                        @update:model-value="
                            (value) =>
                                visit({
                                    owes_for:
                                        value === ALL ? null : String(value),
                                })
                        "
                    >
                        <SelectTrigger class="bg-background w-full">
                            <SelectValue placeholder="Any period" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL">Any open debt</SelectItem>
                            <SelectItem
                                v-for="option in filter_options.owes_for"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
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
                        :disabled="!filters_active"
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
                            {{ rows.length }}
                            <template v-if="filters_active">
                                of {{ unpaid_count }}
                            </template>
                            Properties
                        </span>
                        <span>Balance ↓</span>
                    </div>

                    <div
                        v-if="empty_state === 'no_matches'"
                        class="text-muted-foreground flex flex-1 flex-col items-center justify-center gap-2 px-4 py-12 text-center text-sm"
                    >
                        <p class="text-foreground font-medium">No matches</p>
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
                        <li v-for="row in rows" :key="row.property_id">
                            <button
                                type="button"
                                class="flex w-full flex-col gap-1.5 border-b px-3 py-3 text-left text-sm last:border-b-0"
                                :class="
                                    values.property === row.property_id
                                        ? 'bg-background font-medium'
                                        : 'hover:bg-background/70'
                                "
                                @click="selectProperty(row.property_id)"
                            >
                                <span class="flex justify-between gap-2">
                                    <span class="truncate">{{
                                        row.label
                                    }}</span>
                                    <span
                                        class="shrink-0 font-semibold tabular-nums"
                                    >
                                        {{ formatPhp(row.outstanding_balance) }}
                                    </span>
                                </span>
                                <span
                                    class="flex flex-wrap items-center gap-1.5"
                                >
                                    <span
                                        class="inline-block rounded px-1.5 py-0.5 text-[10px] font-medium"
                                        :class="
                                            periodTone(row.this_period_status)
                                        "
                                    >
                                        {{ this_billing_period.label }}:
                                        {{
                                            statusLabel(row.this_period_status)
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
                    <PropertyStatementPanel
                        v-if="selectedStatement"
                        :statement="selectedStatement"
                        @select-period="selectPeriod"
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
