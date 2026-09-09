<script setup lang="ts">
/**
 * Variant B — Outstanding-first notice + remittance stub.
 * Hierarchy: big Outstanding Balance, then open periods, then this month
 * detail. Bottom third is a perforated stub the Treasurer can tear off.
 * Different from the classic month-primary letter.
 */
import {
    formatPhp,
    letterhead,
    payeeLabel,
    paymentChannels,
    type BillProperty,
} from './data';

defineOptions({ name: 'BillVariantB' });

defineProps<{ bill: BillProperty; issuedOn: string }>();
</script>

<template>
    <article
        class="bill-sheet mx-auto w-full max-w-[210mm] bg-white text-zinc-900 shadow-md print:shadow-none"
    >
        <div
            class="flex min-h-[297mm] flex-col border border-zinc-300 print:border-0"
        >
            <div class="flex flex-1 flex-col p-8">
                <header class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] tracking-[0.25em] uppercase">
                            {{ letterhead.shortName }}
                        </p>
                        <h1 class="mt-1 text-xl font-bold tracking-tight">
                            Payment notice
                        </h1>
                        <p class="text-sm text-zinc-600">
                            {{ bill.thisMonth.label }}
                        </p>
                    </div>
                    <div class="text-right text-xs text-zinc-500">
                        <p>{{ issuedOn }}</p>
                        <p>{{ bill.blockLot }}</p>
                    </div>
                </header>

                <section class="mt-8 rounded-none bg-zinc-950 px-5 py-6 text-zinc-50">
                    <p class="text-xs tracking-wide text-zinc-400 uppercase">
                        Outstanding Balance
                    </p>
                    <p class="mt-1 text-4xl font-bold tracking-tight tabular-nums">
                        {{ formatPhp(bill.outstandingBalance) }}
                    </p>
                    <p class="mt-2 text-sm text-zinc-300">
                        {{ payeeLabel(bill) }} · {{ bill.propertyLabel }}
                        <template v-if="bill.address">
                            · {{ bill.address }}
                        </template>
                    </p>
                </section>

                <section class="mt-6">
                    <h2
                        class="text-xs font-semibold tracking-wide text-zinc-500 uppercase"
                    >
                        What this covers
                    </h2>
                    <ul class="mt-2 divide-y divide-zinc-200 border-y border-zinc-200 text-sm">
                        <li
                            v-if="bill.openingBalanceRemaining > 0"
                            class="flex justify-between py-2"
                        >
                            <span>Opening Balance (prior arrears)</span>
                            <span class="tabular-nums">{{
                                formatPhp(bill.openingBalanceRemaining)
                            }}</span>
                        </li>
                        <li
                            v-for="p in bill.olderUnpaid"
                            :key="p.label"
                            class="flex justify-between py-2"
                        >
                            <span>{{ p.label }} (unpaid)</span>
                            <span class="tabular-nums">{{
                                formatPhp(p.remaining)
                            }}</span>
                        </li>
                        <li
                            v-if="bill.thisMonth.remaining > 0"
                            class="flex justify-between py-2 font-medium"
                        >
                            <span>{{ bill.thisMonth.label }}</span>
                            <span class="tabular-nums">{{
                                formatPhp(bill.thisMonth.remaining)
                            }}</span>
                        </li>
                        <li
                            v-else
                            class="flex justify-between py-2 text-zinc-500"
                        >
                            <span>{{ bill.thisMonth.label }}</span>
                            <span>Paid</span>
                        </li>
                    </ul>
                </section>

                <section
                    v-if="bill.thisMonth.remaining > 0"
                    class="mt-4 text-sm"
                >
                    <p class="text-xs text-zinc-500 uppercase">
                        {{ bill.thisMonth.label }} Fee Types
                    </p>
                    <div
                        v-for="line in bill.thisMonth.lines"
                        :key="line.name"
                        class="mt-1 flex justify-between"
                    >
                        <span>{{ line.name }}</span>
                        <span class="tabular-nums">{{
                            formatPhp(line.amount)
                        }}</span>
                    </div>
                </section>

                <section class="mt-6 text-xs text-zinc-600">
                    <p class="font-medium text-zinc-800">Pay via</p>
                    <p class="mt-1">
                        {{
                            paymentChannels
                                .map((c) => `${c.method}: ${c.detail}`)
                                .join(' · ')
                        }}
                    </p>
                </section>
            </div>

            <!-- remittance stub -->
            <div
                class="relative border-t-2 border-dashed border-zinc-400 bg-zinc-50 px-8 py-5"
            >
                <p
                    class="absolute -top-2.5 left-1/2 -translate-x-1/2 bg-zinc-50 px-2 text-[10px] tracking-widest text-zinc-400 uppercase"
                >
                    Tear off · Treasurer copy
                </p>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-zinc-500">Property</p>
                        <p class="font-semibold">{{ bill.propertyLabel }}</p>
                        <p class="text-xs">{{ payeeLabel(bill) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-zinc-500">Amount enclosed</p>
                        <p class="text-xl font-bold tabular-nums">
                            {{ formatPhp(bill.outstandingBalance) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500">Period on notice</p>
                        <p>{{ bill.thisMonth.label }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500">
                            Method / reference (write in)
                        </p>
                        <div
                            class="mt-1 h-8 border-b border-zinc-400"
                        />
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>
