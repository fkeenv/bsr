<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { show as printedBillShow } from '@/routes/officer/printed-bills';
import type { StatementOfAccountPage } from '@/types/statement-of-account';

const props = defineProps<{
    statement: StatementOfAccountPage;
}>();

const emit = defineEmits<{
    selectPeriod: [chargeId: number];
}>();

function formatPhp(value: string | number): string {
    const amountValue =
        typeof value === 'number' ? value : Number.parseFloat(value);

    return `₱${Number.isFinite(amountValue) ? amountValue.toFixed(2) : '0.00'}`;
}

function statusLabel(status: string): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}

function methodLabel(value: string): string {
    const labels: Record<string, string> = {
        cash: 'Cash',
        bank: 'Bank',
        gcash: 'GCash',
        maya: 'Maya',
    };

    return labels[value] ?? value;
}

const printUrl = computed((): string | null => {
    const period = props.statement.selected_period;

    if (!period) {
        return null;
    }

    return printedBillShow.url(props.statement.property.id, {
        query: {
            year: period.year,
            month: period.month,
        },
    });
});
</script>

<template>
    <div class="flex h-full min-h-0 flex-col gap-3">
        <div>
            <p class="text-muted-foreground text-xs uppercase">
                Property · same surface Members see
            </p>
            <h2 class="text-xl font-semibold">
                {{ statement.property.label }}
            </h2>
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
                        {{ formatPhp(statement.outstanding_balance) }}
                    </p>
                    <dl class="mt-3 space-y-1 text-xs">
                        <div
                            v-if="
                                Number.parseFloat(
                                    statement.remaining_opening_balance,
                                ) > 0
                            "
                            class="flex justify-between"
                        >
                            <dt class="text-muted-foreground">
                                Opening Balance
                            </dt>
                            <dd class="tabular-nums">
                                {{
                                    formatPhp(
                                        statement.remaining_opening_balance,
                                    )
                                }}
                            </dd>
                        </div>
                        <div
                            v-if="
                                Number.parseFloat(statement.prepaid_balance) > 0
                            "
                            class="flex justify-between"
                        >
                            <dt class="text-muted-foreground">
                                Prepaid remaining
                            </dt>
                            <dd class="tabular-nums">
                                {{ formatPhp(statement.prepaid_balance) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div
                    v-if="statement.pending_declarations.length"
                    class="border-b bg-amber-50 px-4 py-3 text-xs text-amber-950 dark:bg-amber-950/40 dark:text-amber-50"
                >
                    <p class="font-semibold">Pending declarations</p>
                    <p
                        v-for="declaration in statement.pending_declarations"
                        :key="declaration.id"
                    >
                        {{ formatPhp(declaration.amount) }} ·
                        {{ methodLabel(declaration.method) }}
                    </p>
                </div>

                <nav class="max-h-[40vh] overflow-y-auto md:max-h-none">
                    <button
                        v-for="period in statement.periods"
                        :key="period.charge_id"
                        type="button"
                        class="flex w-full items-center justify-between gap-2 border-b px-4 py-3 text-left text-sm last:border-b-0"
                        :class="
                            statement.selected_charge_id === period.charge_id
                                ? 'bg-background font-medium'
                                : 'hover:bg-background/60'
                        "
                        @click="emit('selectPeriod', period.charge_id)"
                    >
                        <span>{{ period.label }}</span>
                        <Badge variant="secondary" class="shrink-0 text-[10px]">
                            {{ statusLabel(period.status) }}
                        </Badge>
                    </button>
                    <p
                        v-if="statement.periods.length === 0"
                        class="text-muted-foreground px-4 py-6 text-sm"
                    >
                        No Billing Periods have been levied yet.
                    </p>
                </nav>
            </aside>

            <section
                v-if="statement.selected_period"
                class="flex flex-col gap-4 p-4"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <h3 class="text-lg font-semibold">
                            Statement of Account ·
                            {{ statement.selected_period.label }}
                        </h3>
                        <p class="text-muted-foreground text-sm">
                            Remaining
                            {{ formatPhp(statement.selected_period.remaining) }}
                            of
                            {{
                                formatPhp(
                                    statement.selected_period.charge_total,
                                )
                            }}
                        </p>
                    </div>
                    <Button
                        v-if="printUrl"
                        as-child
                        variant="outline"
                        size="sm"
                        class="shrink-0"
                    >
                        <a :href="printUrl">Print Printed Bill</a>
                    </Button>
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
                            v-for="line in statement.selected_period.lines"
                            :key="line.id"
                            class="border-b"
                        >
                            <td class="py-2">{{ line.fee_type_name }}</td>
                            <td class="py-2 text-right tabular-nums">
                                {{ formatPhp(line.amount) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div>
                    <h4 class="mb-2 text-sm font-semibold">Payments</h4>
                    <p
                        v-if="!statement.selected_period.payments.length"
                        class="text-muted-foreground text-sm"
                    >
                        No Payments on this Charge yet.
                    </p>
                    <ul v-else class="space-y-2 text-sm">
                        <li
                            v-for="payment in statement.selected_period
                                .payments"
                            :key="payment.id"
                            class="flex justify-between gap-2 border-b py-2 last:border-b-0"
                        >
                            <span>
                                {{ methodLabel(payment.method) }}
                                <span
                                    v-if="payment.reference"
                                    class="text-muted-foreground"
                                >
                                    · {{ payment.reference }}
                                </span>
                            </span>
                            <span class="tabular-nums">{{
                                formatPhp(payment.amount)
                            }}</span>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</template>
