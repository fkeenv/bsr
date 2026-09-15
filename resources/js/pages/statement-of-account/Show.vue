<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { dashboard } from '@/routes';
import { show as statementOfAccount } from '@/routes/statement-of-account';
import type { StatementOfAccountPage } from '@/types/statement-of-account';

const props = defineProps<StatementOfAccountPage>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Statement of Account',
            },
        ],
    },
});

const sheetOpen = ref(false);
const amount = ref(props.outstanding_balance);
const method = ref('bank');
const reference = ref('');

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

function selectPeriod(chargeId: number): void {
    router.get(
        statementOfAccount.url(props.property.id, {
            query: { charge: chargeId },
        }),
        {},
        { preserveState: true, preserveScroll: true },
    );
}

function switchProperty(propertyId: number): void {
    if (propertyId === props.property.id) {
        return;
    }

    router.get(statementOfAccount.url(propertyId));
}

function openDeclare(): void {
    amount.value = props.outstanding_balance;
    reference.value = '';
    sheetOpen.value = true;
}

function closeSheet(): void {
    sheetOpen.value = false;
}
</script>

<template>
    <Head :title="`Statement of Account · ${property.label}`" />

    <div class="flex flex-col gap-4 p-4 pb-24">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Statement of Account"
                :description="property.label"
            />

            <div v-if="switcher.length > 1" class="w-full sm:max-w-xs">
                <Label for="property-switcher">Property</Label>
                <Select
                    :model-value="String(property.id)"
                    @update:model-value="
                        (value) => switchProperty(Number(value))
                    "
                >
                    <SelectTrigger id="property-switcher" class="mt-1 w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in switcher"
                            :key="option.property_id"
                            :value="String(option.property_id)"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div
            class="grid min-h-[70vh] overflow-hidden rounded-xl border md:grid-cols-[minmax(240px,320px)_1fr]"
        >
            <aside class="bg-muted/40 border-b md:border-r md:border-b-0">
                <div class="border-b px-4 py-4">
                    <p class="text-muted-foreground text-xs">
                        Outstanding Balance
                    </p>
                    <p class="text-3xl font-semibold tabular-nums">
                        {{ formatPhp(outstanding_balance) }}
                    </p>
                    <dl class="mt-3 space-y-1 text-xs">
                        <div
                            v-if="
                                Number.parseFloat(remaining_opening_balance) > 0
                            "
                            class="flex justify-between"
                        >
                            <dt class="text-muted-foreground">
                                Opening Balance
                            </dt>
                            <dd class="tabular-nums">
                                {{ formatPhp(remaining_opening_balance) }}
                            </dd>
                        </div>
                        <div
                            v-if="Number.parseFloat(prepaid_balance) > 0"
                            class="flex justify-between"
                        >
                            <dt class="text-muted-foreground">
                                Prepaid remaining
                            </dt>
                            <dd class="tabular-nums">
                                {{ formatPhp(prepaid_balance) }}
                            </dd>
                        </div>
                    </dl>
                    <Button
                        type="button"
                        size="sm"
                        class="mt-4 w-full"
                        @click="openDeclare"
                    >
                        I paid
                    </Button>
                </div>

                <div
                    v-if="pending_declarations.length"
                    class="border-b bg-amber-50 px-4 py-3 text-xs text-amber-950 dark:bg-amber-950/40 dark:text-amber-50"
                >
                    <p class="font-semibold">Pending</p>
                    <p
                        v-for="declaration in pending_declarations"
                        :key="declaration.id"
                    >
                        {{ formatPhp(declaration.amount) }} ·
                        {{ methodLabel(declaration.method) }}
                    </p>
                </div>

                <nav class="max-h-[50vh] overflow-y-auto md:max-h-none">
                    <button
                        v-for="period in periods"
                        :key="period.charge_id"
                        type="button"
                        class="flex w-full items-center justify-between gap-2 border-b px-4 py-3 text-left text-sm last:border-b-0"
                        :class="
                            selected_charge_id === period.charge_id
                                ? 'bg-background font-medium'
                                : 'hover:bg-background/60'
                        "
                        @click="selectPeriod(period.charge_id)"
                    >
                        <span>{{ period.label }}</span>
                        <span class="flex items-center gap-2">
                            <span
                                v-if="Number.parseFloat(period.remaining) > 0"
                                class="text-xs tabular-nums"
                            >
                                {{ formatPhp(period.remaining) }}
                            </span>
                            <Badge
                                :variant="
                                    period.status === 'paid'
                                        ? 'secondary'
                                        : 'outline'
                                "
                                class="text-[10px]"
                            >
                                {{ statusLabel(period.status) }}
                            </Badge>
                        </span>
                    </button>
                    <p
                        v-if="periods.length === 0"
                        class="text-muted-foreground px-4 py-6 text-sm"
                    >
                        No Billing Periods have been levied yet.
                    </p>
                </nav>
            </aside>

            <main v-if="selected_period" class="flex flex-col gap-6 p-5 md:p-8">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-muted-foreground text-xs">
                            Statement of Account
                        </p>
                        <h2 class="text-2xl font-semibold">
                            {{ selected_period.label }}
                        </h2>
                    </div>
                    <div class="text-right">
                        <p class="text-muted-foreground text-xs">Remaining</p>
                        <p class="text-2xl font-semibold tabular-nums">
                            {{ formatPhp(selected_period.remaining) }}
                        </p>
                    </div>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="text-muted-foreground border-b text-left text-xs"
                        >
                            <th class="pb-2 font-medium">Fee Type</th>
                            <th class="pb-2 text-right font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="line in selected_period.lines"
                            :key="line.id"
                            class="border-b"
                        >
                            <td class="py-3">{{ line.fee_type_name }}</td>
                            <td class="py-3 text-right tabular-nums">
                                {{ formatPhp(line.amount) }}
                            </td>
                        </tr>
                        <tr class="font-medium">
                            <td class="pt-3">Charge total</td>
                            <td class="pt-3 text-right tabular-nums">
                                {{ formatPhp(selected_period.charge_total) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div>
                    <h3 class="mb-3 text-sm font-semibold">
                        Payments recorded against this Charge
                    </h3>
                    <div
                        v-if="selected_period.payments.length"
                        class="overflow-hidden rounded-lg border"
                    >
                        <table class="w-full text-sm">
                            <thead
                                class="bg-muted/50 text-muted-foreground text-xs"
                            >
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">
                                        Date
                                    </th>
                                    <th class="px-3 py-2 text-left font-medium">
                                        Method
                                    </th>
                                    <th class="px-3 py-2 text-left font-medium">
                                        Ref
                                    </th>
                                    <th
                                        class="px-3 py-2 text-right font-medium"
                                    >
                                        Amount
                                    </th>
                                    <th
                                        class="px-3 py-2 text-right font-medium"
                                    >
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="payment in selected_period.payments"
                                    :key="payment.id"
                                    class="border-t"
                                >
                                    <td class="px-3 py-2">
                                        {{ payment.recorded_on ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ methodLabel(payment.method) }}
                                    </td>
                                    <td class="text-muted-foreground px-3 py-2">
                                        {{ payment.reference ?? '—' }}
                                    </td>
                                    <td
                                        class="px-3 py-2 text-right tabular-nums"
                                    >
                                        {{ formatPhp(payment.amount) }}
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <Badge
                                            :variant="
                                                payment.status === 'confirmed'
                                                    ? 'secondary'
                                                    : 'outline'
                                            "
                                        >
                                            {{ payment.status }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-muted-foreground text-sm">
                        No Payments allocated to this Charge yet.
                    </p>
                </div>
            </main>

            <main
                v-else
                class="text-muted-foreground flex items-center justify-center p-8 text-sm"
            >
                Select a Billing Period to view its Statement of Account.
            </main>
        </div>

        <Sheet v-model:open="sheetOpen">
            <SheetContent side="right" class="sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>I paid</SheetTitle>
                    <SheetDescription>
                        Declare a Payment for this Property. An Officer confirms
                        later — pending never reduces Outstanding Balance.
                    </SheetDescription>
                </SheetHeader>

                <Form
                    v-bind="PaymentController.store.form()"
                    enctype="multipart/form-data"
                    class="flex flex-1 flex-col gap-4"
                    :options="{ preserveScroll: true }"
                    @success="closeSheet"
                    v-slot="{ errors, processing }"
                >
                    <input
                        type="hidden"
                        name="property_id"
                        :value="property.id"
                    />

                    <div class="grid gap-4 px-4">
                        <div class="grid gap-2">
                            <Label for="amount">Amount</Label>
                            <Input
                                id="amount"
                                name="amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                required
                                v-model="amount"
                            />
                            <InputError :message="errors.amount" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="method">Method</Label>
                            <select
                                id="method"
                                name="method"
                                required
                                v-model="method"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                            >
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                                <option value="gcash">GCash</option>
                                <option value="maya">Maya</option>
                            </select>
                            <InputError :message="errors.method" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="reference">Reference</Label>
                            <Input
                                id="reference"
                                name="reference"
                                v-model="reference"
                            />
                            <InputError :message="errors.reference" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="screenshot">Receipt screenshot</Label>
                            <Input
                                id="screenshot"
                                name="screenshot"
                                type="file"
                                accept="image/*"
                                required
                            />
                            <InputError :message="errors.screenshot" />
                        </div>
                    </div>

                    <SheetFooter>
                        <Button type="submit" :disabled="processing">
                            Declare Payment
                        </Button>
                    </SheetFooter>
                </Form>
            </SheetContent>
        </Sheet>
    </div>
</template>
