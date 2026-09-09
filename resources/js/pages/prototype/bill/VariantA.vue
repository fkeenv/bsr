<script setup lang="ts">
/**
 * Variant A — Classic month letter.
 * One A4 page per Property. Hierarchy: letterhead → this Billing Period →
 * Fee Type lines → balance forward → amount due → how to pay.
 * Closest to a familiar paper HOA bill.
 */
import {
    formatPhp,
    letterhead,
    payeeLabel,
    paymentChannels,
    type BillProperty,
} from './data';

defineOptions({ name: 'BillVariantA' });

defineProps<{ bill: BillProperty; issuedOn: string }>();

function balanceForward(bill: BillProperty): number {
    return (
        bill.openingBalanceRemaining +
        bill.olderUnpaid.reduce((s, p) => s + p.remaining, 0)
    );
}
</script>

<template>
    <article
        class="bill-sheet mx-auto w-full max-w-[210mm] bg-white text-zinc-900 shadow-md print:shadow-none"
    >
        <div
            class="flex min-h-[297mm] flex-col border border-zinc-300 p-10 print:border-0"
        >
            <header class="border-b-2 border-zinc-900 pb-4">
                <p class="text-xs tracking-[0.2em] uppercase">
                    {{ letterhead.shortName }}
                </p>
                <h1 class="mt-1 font-serif text-2xl leading-tight font-semibold">
                    {{ letterhead.name }}
                </h1>
                <p
                    v-for="line in letterhead.addressLines"
                    :key="line"
                    class="text-sm text-zinc-600"
                >
                    {{ line }}
                </p>
                <div
                    class="mt-3 flex flex-wrap items-end justify-between gap-2 text-sm"
                >
                    <p>
                        <span class="text-zinc-500">Issued</span>
                        {{ issuedOn }}
                    </p>
                    <p class="font-medium">
                        Billing Period:
                        <span class="underline decoration-2">{{
                            bill.thisMonth.label
                        }}</span>
                    </p>
                </div>
            </header>

            <section class="mt-6 grid gap-1 text-sm">
                <p class="text-xs tracking-wide text-zinc-500 uppercase">
                    Bill to
                </p>
                <p class="text-lg font-semibold">{{ payeeLabel(bill) }}</p>
                <p>{{ bill.propertyLabel }}</p>
                <p v-if="bill.address" class="text-zinc-600">
                    {{ bill.address }}
                </p>
            </section>

            <section class="mt-8">
                <h2
                    class="mb-2 border-b border-zinc-300 pb-1 text-sm font-semibold tracking-wide uppercase"
                >
                    Charges for {{ bill.thisMonth.label }}
                </h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-zinc-500">
                            <th class="py-1 font-medium">Fee Type</th>
                            <th class="py-1 text-right font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="line in bill.thisMonth.lines"
                            :key="line.name"
                            class="border-t border-zinc-100"
                        >
                            <td class="py-2">{{ line.name }}</td>
                            <td class="py-2 text-right tabular-nums">
                                {{ formatPhp(line.amount) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-zinc-400">
                            <td class="py-2 font-medium">This period</td>
                            <td
                                class="py-2 text-right font-medium tabular-nums"
                            >
                                {{
                                    formatPhp(
                                        bill.thisMonth.lines.reduce(
                                            (s, l) => s + l.amount,
                                            0,
                                        ),
                                    )
                                }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </section>

            <section class="mt-6 text-sm">
                <div class="flex justify-between border-t border-dashed border-zinc-300 py-2">
                    <span>Balance forward (prior arrears)</span>
                    <span class="tabular-nums">{{
                        formatPhp(balanceForward(bill))
                    }}</span>
                </div>
                <p
                    v-if="bill.openingBalanceRemaining > 0"
                    class="text-xs text-zinc-500"
                >
                    Includes Opening Balance
                    {{ formatPhp(bill.openingBalanceRemaining) }}
                    <template v-if="bill.olderUnpaid.length">
                        and unpaid
                        {{
                            bill.olderUnpaid.map((p) => p.label).join(', ')
                        }}
                    </template>
                </p>
                <p
                    v-else-if="bill.olderUnpaid.length"
                    class="text-xs text-zinc-500"
                >
                    Unpaid
                    {{ bill.olderUnpaid.map((p) => p.label).join(', ') }}
                </p>
            </section>

            <section
                class="mt-6 flex items-center justify-between border-2 border-zinc-900 px-4 py-3"
            >
                <div>
                    <p class="text-xs tracking-wide uppercase">Amount due</p>
                    <p class="text-xs text-zinc-500">
                        Outstanding Balance for this Property
                    </p>
                </div>
                <p class="font-serif text-3xl font-semibold tabular-nums">
                    {{ formatPhp(bill.outstandingBalance) }}
                </p>
            </section>

            <section class="mt-8 flex-1 text-sm">
                <h2 class="mb-2 text-sm font-semibold tracking-wide uppercase">
                    How to pay
                </h2>
                <ul class="space-y-2">
                    <li
                        v-for="ch in paymentChannels"
                        :key="ch.method"
                        class="flex gap-3"
                    >
                        <span class="w-28 shrink-0 font-medium">{{
                            ch.method
                        }}</span>
                        <span class="text-zinc-600">{{ ch.detail }}</span>
                    </li>
                </ul>
                <p class="mt-4 text-xs text-zinc-500">
                    {{ letterhead.treasurer }} · {{ letterhead.contact }}
                </p>
            </section>

            <footer
                class="mt-auto border-t border-zinc-200 pt-3 text-xs text-zinc-500"
            >
                This notice is not a receipt. Confirmed Payments appear on your
                Statement of Account in the member platform. Please name
                {{ bill.propertyLabel }} on every transfer.
            </footer>
        </div>
    </article>
</template>
