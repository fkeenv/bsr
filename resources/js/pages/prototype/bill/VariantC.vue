<script setup lang="ts">
/**
 * Variant C — Officer batch print run.
 * Not a single family letter: a print-queue surface for one Billing Period.
 * Shows unpaid Properties as a roster, then a 2-up sheet of short slips
 * meant to be cut and delivered. Settles single-vs-batch visually.
 */
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    batchRoster,
    formatPhp,
    letterhead,
    payeeLabel,
    type BillProperty,
    type ScenarioKey,
} from './data';

defineOptions({ name: 'BillVariantC' });

const props = defineProps<{
    bill: BillProperty;
    scenario: ScenarioKey;
    issuedOn: string;
}>();

const roster = computed(() => batchRoster(props.scenario));

const periodLabel = computed(
    () => props.bill.thisMonth.label || 'September 2026',
);

const totalDue = computed(() =>
    roster.value.reduce((s, p) => s + p.outstandingBalance, 0),
);
</script>

<template>
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
        <!-- Officer chrome (screen only; muted when printing) -->
        <section
            class="rounded-lg border border-zinc-300 bg-zinc-100 p-4 print:hidden"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs tracking-wide text-zinc-500 uppercase">
                        Officer · Print run
                    </p>
                    <h1 class="text-xl font-semibold">
                        Unpaid Properties · {{ periodLabel }}
                    </h1>
                    <p class="mt-1 text-sm text-zinc-600">
                        {{ roster.length }} Properties ·
                        {{ formatPhp(totalDue) }} total Outstanding Balance
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" size="sm" disabled>
                        Print this Property
                    </Button>
                    <Button type="button" size="sm" disabled>
                        Print all unpaid ({{ roster.length }})
                    </Button>
                </div>
            </div>

            <table class="mt-4 w-full text-left text-sm">
                <thead class="text-xs text-zinc-500 uppercase">
                    <tr>
                        <th class="py-1 font-medium">Block + Lot</th>
                        <th class="py-1 font-medium">Recorded owner</th>
                        <th class="py-1 text-right font-medium">
                            Outstanding
                        </th>
                        <th class="py-1 text-right font-medium">
                            This period
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in roster"
                        :key="row.blockLot"
                        class="border-t border-zinc-200"
                        :class="
                            row.blockLot === bill.blockLot
                                ? 'bg-amber-50'
                                : undefined
                        "
                    >
                        <td class="py-2 font-medium">
                            {{ row.propertyLabel }}
                        </td>
                        <td class="py-2 text-zinc-600">
                            {{ row.recordedOwner ?? '—' }}
                        </td>
                        <td class="py-2 text-right tabular-nums">
                            {{ formatPhp(row.outstandingBalance) }}
                        </td>
                        <td class="py-2 text-right tabular-nums text-zinc-600">
                            {{
                                row.thisMonth.remaining > 0
                                    ? formatPhp(row.thisMonth.remaining)
                                    : 'Paid'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <p class="text-center text-xs text-zinc-500 print:hidden">
            Below: batch sheet (2-up short slips). Cut along the dashed line.
            Letterhead is abbreviated — full letter is Variant A.
        </p>

        <!-- Printable 2-up batch sheet -->
        <article
            class="bill-sheet border border-zinc-300 bg-white text-zinc-900 shadow-md print:border-0 print:shadow-none"
        >
            <div
                class="grid min-h-[297mm] grid-cols-1 gap-0 md:grid-cols-2"
            >
                <div
                    v-for="(slip, idx) in roster.slice(0, 4)"
                    :key="slip.blockLot"
                    class="flex min-h-[148mm] flex-col border-zinc-300 p-5"
                    :class="{
                        'border-b md:border-r': idx % 2 === 0,
                        'border-b': idx < 2,
                        'md:border-l-0': idx % 2 === 1,
                    }"
                >
                    <header class="border-b border-zinc-900 pb-2">
                        <p class="text-[10px] tracking-[0.2em] uppercase">
                            {{ letterhead.shortName }}
                        </p>
                        <p class="text-sm font-semibold">
                            Dues notice · {{ periodLabel }}
                        </p>
                        <p class="text-[11px] text-zinc-500">
                            Issued {{ issuedOn }}
                        </p>
                    </header>

                    <div class="mt-3 text-sm">
                        <p class="text-lg font-bold">
                            {{ slip.propertyLabel }}
                        </p>
                        <p>{{ payeeLabel(slip) }}</p>
                        <p
                            v-if="slip.address"
                            class="text-xs text-zinc-500"
                        >
                            {{ slip.address }}
                        </p>
                    </div>

                    <div
                        class="mt-4 flex items-end justify-between border border-zinc-900 px-3 py-2"
                    >
                        <span class="text-xs tracking-wide uppercase"
                            >Amount due</span
                        >
                        <span class="text-2xl font-bold tabular-nums">{{
                            formatPhp(slip.outstandingBalance)
                        }}</span>
                    </div>

                    <ul class="mt-3 flex-1 space-y-1 text-xs text-zinc-600">
                        <li v-if="slip.openingBalanceRemaining > 0">
                            Opening Balance
                            {{ formatPhp(slip.openingBalanceRemaining) }}
                        </li>
                        <li v-for="p in slip.olderUnpaid" :key="p.label">
                            {{ p.label }} · {{ formatPhp(p.remaining) }}
                        </li>
                        <li v-if="slip.thisMonth.remaining > 0" class="font-medium text-zinc-800">
                            {{ slip.thisMonth.label }} ·
                            {{ formatPhp(slip.thisMonth.remaining) }}
                            <span class="font-normal text-zinc-500">
                                ({{
                                    slip.thisMonth.lines
                                        .map((l) => l.name)
                                        .join(' + ')
                                }})
                            </span>
                        </li>
                    </ul>

                    <p class="mt-auto pt-2 text-[10px] text-zinc-500">
                        Cash · bank · GCash · Maya to the Treasurer. Name
                        {{ slip.propertyLabel }} on the transfer. Not a receipt.
                    </p>
                </div>
            </div>
        </article>
    </div>
</template>
