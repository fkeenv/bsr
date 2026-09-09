<script setup lang="ts">
/**
 * Shared Property drill-in pane for unpaid-roster prototypes.
 * Mirrors Member SoA Variant C (ADR-0011): Outstanding + periods + monthly SoA.
 * Read-only stubs — Officers confirm Payments elsewhere in the thin Officer set.
 */
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatPhp,
    methodLabel,
    statusLabel,
    type RosterProperty,
} from './data';

defineOptions({ name: 'PropertyDrillIn' });

const props = defineProps<{
    property: RosterProperty;
    /** Show a back control (full-page drill-in). Split layouts pass false. */
    showBack?: boolean;
}>();

const emit = defineEmits<{
    back: [];
}>();

const selectedId = ref(
    props.property.periods.find((p) => p.status !== 'paid')?.id ??
        props.property.periods[0]?.id,
);

watch(
    () => props.property.id,
    () => {
        selectedId.value =
            props.property.periods.find((p) => p.status !== 'paid')?.id ??
            props.property.periods[0]?.id;
    },
);

const selected = computed(
    () =>
        props.property.periods.find((p) => p.id === selectedId.value) ??
        props.property.periods[0],
);
</script>

<template>
    <div class="flex h-full min-h-0 flex-col gap-3">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <Button
                    v-if="showBack"
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="-ml-2 mb-1"
                    @click="emit('back')"
                >
                    ← Unpaid roster
                </Button>
                <p class="text-muted-foreground text-xs uppercase">
                    Property · same surface Members see
                </p>
                <h2 class="text-xl font-semibold">
                    {{ property.propertyLabel }}
                </h2>
                <p
                    v-if="property.recordedOwner"
                    class="text-muted-foreground text-sm"
                >
                    Recorded owner: {{ property.recordedOwner }}
                </p>
            </div>
            <Badge variant="outline">Drill-in</Badge>
        </div>

        <div
            class="grid min-h-[56vh] flex-1 overflow-hidden rounded-xl border md:grid-cols-[minmax(220px,280px)_1fr]"
        >
            <aside class="bg-muted/40 border-b md:border-r md:border-b-0">
                <div class="border-b px-4 py-4">
                    <p class="text-muted-foreground text-xs">
                        Outstanding Balance
                    </p>
                    <p class="text-3xl font-semibold tabular-nums">
                        {{ formatPhp(property.outstandingBalance) }}
                    </p>
                    <dl class="mt-3 space-y-1 text-xs">
                        <div
                            v-if="property.openingBalanceRemaining > 0"
                            class="flex justify-between"
                        >
                            <dt class="text-muted-foreground">
                                Opening Balance
                            </dt>
                            <dd class="tabular-nums">
                                {{
                                    formatPhp(
                                        property.openingBalanceRemaining,
                                    )
                                }}
                            </dd>
                        </div>
                        <div
                            v-if="property.prepaidRemaining > 0"
                            class="flex justify-between"
                        >
                            <dt class="text-muted-foreground">
                                Prepaid remaining
                            </dt>
                            <dd class="tabular-nums">
                                {{ formatPhp(property.prepaidRemaining) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div
                    v-if="property.pendingDeclarations.length"
                    class="border-b bg-amber-50 px-4 py-3 text-xs text-amber-950"
                >
                    <p class="font-semibold">Pending declarations</p>
                    <p
                        v-for="p in property.pendingDeclarations"
                        :key="p.id"
                    >
                        {{ formatPhp(p.amount) }} ·
                        {{ methodLabel(p.method) }}
                    </p>
                </div>

                <nav class="max-h-[40vh] overflow-y-auto md:max-h-none">
                    <button
                        v-for="p in property.periods"
                        :key="p.id"
                        type="button"
                        class="flex w-full items-center justify-between gap-2 border-b px-4 py-3 text-left text-sm last:border-b-0"
                        :class="
                            selectedId === p.id
                                ? 'bg-background font-medium'
                                : 'hover:bg-background/60'
                        "
                        @click="selectedId = p.id"
                    >
                        <span>{{ p.label }}</span>
                        <Badge
                            variant="secondary"
                            class="shrink-0 text-[10px]"
                        >
                            {{ statusLabel(p.status) }}
                        </Badge>
                    </button>
                </nav>
            </aside>

            <section v-if="selected" class="flex flex-col gap-4 p-4">
                <div>
                    <h3 class="text-lg font-semibold">
                        Statement of Account · {{ selected.label }}
                    </h3>
                    <p class="text-muted-foreground text-sm">
                        Remaining
                        {{ formatPhp(selected.remaining) }} of
                        {{ formatPhp(selected.chargeTotal) }}
                    </p>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="text-muted-foreground border-b text-left text-xs"
                        >
                            <th class="py-2 font-medium">Fee Type</th>
                            <th class="py-2 text-right font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="line in selected.lines"
                            :key="line.name"
                            class="border-b"
                        >
                            <td class="py-2">{{ line.name }}</td>
                            <td class="py-2 text-right tabular-nums">
                                {{ formatPhp(line.amount) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div>
                    <h4 class="mb-2 text-sm font-semibold">Payments</h4>
                    <p
                        v-if="!selected.payments.length"
                        class="text-muted-foreground text-sm"
                    >
                        No Payments on this Charge yet.
                    </p>
                    <ul v-else class="space-y-2 text-sm">
                        <li
                            v-for="pay in selected.payments"
                            :key="pay.id"
                            class="flex flex-wrap items-center justify-between gap-2 border-b pb-2"
                        >
                            <span>
                                {{ formatPhp(pay.amount) }} ·
                                {{ methodLabel(pay.method) }}
                                <span
                                    v-if="pay.reference"
                                    class="text-muted-foreground"
                                >
                                    · {{ pay.reference }}
                                </span>
                            </span>
                            <Badge variant="outline">{{ pay.status }}</Badge>
                        </li>
                    </ul>
                </div>

                <p class="text-muted-foreground text-xs">
                    Confirm / reject / void Payments live on the Payments
                    surface — not invented here.
                </p>
            </section>
        </div>
    </div>
</template>
