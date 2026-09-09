<script setup lang="ts">
/**
 * Variant C — Split roster workspace.
 * Left rail: compact unpaid list. Right pane: live Property SoA drill-in
 * (ADR-0011 shape) without leaving the page. Closest to "desk tool".
 */
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import PropertyDrillIn from './PropertyDrillIn.vue';
import {
    formatPhp,
    officerNav,
    sortByOutstandingDesc,
    thisBillingPeriodLabel,
    thisPeriodLabel,
    type RosterScenario,
} from './data';

defineOptions({ name: 'RosterVariantC' });

const props = defineProps<{ data: RosterScenario }>();

const rows = computed(() => sortByOutstandingDesc(props.data.unpaid));

const selectedId = ref<string | null>(rows.value[0]?.id ?? null);

watch(
    () => props.data.unpaid.map((r) => r.id).join(','),
    () => {
        if (!rows.value.find((r) => r.id === selectedId.value)) {
            selectedId.value = rows.value[0]?.id ?? null;
        }
    },
);

const selected = computed(
    () => rows.value.find((r) => r.id === selectedId.value) ?? null,
);

function periodDot(status: 'paid' | 'unpaid' | 'partial'): string {
    if (status === 'unpaid') {
        return 'bg-red-500';
    }
    if (status === 'partial') {
        return 'bg-amber-500';
    }
    return 'bg-emerald-500';
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
            <Badge variant="outline">C · Split workspace</Badge>
        </header>

        <div
            v-if="rows.length === 0"
            class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-6 py-24 text-center"
        >
            <p class="text-lg font-semibold">Roster is clear</p>
            <p class="text-muted-foreground max-w-sm text-sm">
                {{ data.rosterCount }} Properties on the books — none with an
                Outstanding Balance right now.
            </p>
        </div>

        <div
            v-else
            class="grid min-h-[72vh] overflow-hidden rounded-xl border lg:grid-cols-[minmax(260px,340px)_1fr]"
        >
            <aside class="bg-muted/30 flex max-h-[72vh] flex-col border-b lg:border-r lg:border-b-0">
                <div
                    class="text-muted-foreground flex items-center justify-between border-b px-3 py-2 text-xs"
                >
                    <span>{{ rows.length }} Properties</span>
                    <span>Balance ↓</span>
                </div>
                <ul class="flex-1 overflow-y-auto">
                    <li v-for="row in rows" :key="row.id">
                        <button
                            type="button"
                            class="flex w-full items-start gap-2 border-b px-3 py-3 text-left text-sm last:border-b-0"
                            :class="
                                selectedId === row.id
                                    ? 'bg-background font-medium'
                                    : 'hover:bg-background/70'
                            "
                            @click="selectedId = row.id"
                        >
                            <span
                                class="mt-1.5 size-2 shrink-0 rounded-full"
                                :class="periodDot(row.thisPeriodStatus)"
                                :title="
                                    thisPeriodLabel(row.thisPeriodStatus)
                                "
                            />
                            <span class="min-w-0 flex-1">
                                <span class="flex justify-between gap-2">
                                    <span class="truncate">{{
                                        row.propertyLabel
                                    }}</span>
                                    <span class="shrink-0 tabular-nums">
                                        {{
                                            formatPhp(row.outstandingBalance)
                                        }}
                                    </span>
                                </span>
                                <span
                                    class="text-muted-foreground mt-0.5 block truncate text-xs font-normal"
                                >
                                    {{ thisBillingPeriodLabel }}:
                                    {{
                                        thisPeriodLabel(row.thisPeriodStatus)
                                    }}
                                    <template v-if="row.oldestOpenLabel">
                                        · since {{ row.oldestOpenLabel }}
                                    </template>
                                </span>
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
            </div>
        </div>
    </div>
</template>
