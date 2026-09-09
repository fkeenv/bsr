<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ChargeController from '@/actions/App/Http/Controllers/Officer/ChargeController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as chargesIndex } from '@/routes/officer/charges';
import type { Charge, ChargeLine } from '@/types/charge';
import type { FeeType } from '@/types/fee-type';

type EditableLine = {
    id?: number;
    fee_type_id: number | null;
    fee_type_name: string;
    amount: string;
};

type Props = {
    charge: Charge;
    feeTypes: FeeType[];
};

const props = defineProps<Props>();

const lines = ref<EditableLine[]>(
    props.charge.lines.map((line: ChargeLine) => ({
        id: line.id,
        fee_type_id: line.fee_type_id,
        fee_type_name: line.fee_type_name,
        amount: line.amount,
    })),
);

const processing = ref(false);
const errors = ref<Record<string, string>>({});

const periodTitle = computed(
    () => `${props.charge.property_label} · ${props.charge.period_label}`,
);

function addLine(): void {
    const first = props.feeTypes[0];

    lines.value.push({
        fee_type_id: first?.id ?? null,
        fee_type_name: first?.name ?? '',
        amount: first?.amount ?? '0.00',
    });
}

function removeLine(index: number): void {
    lines.value.splice(index, 1);
}

function onFeeTypeChange(index: number, feeTypeId: string): void {
    const feeType = props.feeTypes.find(
        (item) => String(item.id) === feeTypeId,
    );

    if (!feeType) {
        return;
    }

    lines.value[index].fee_type_id = feeType.id;
    lines.value[index].fee_type_name = feeType.name;
    lines.value[index].amount = feeType.amount;
}

function submit(): void {
    if (props.charge.is_frozen) {
        return;
    }

    processing.value = true;
    errors.value = {};

    router.put(
        ChargeController.update.url(props.charge),
        { lines: lines.value },
        {
            preserveScroll: true,
            onError: (formErrors) => {
                errors.value = formErrors;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
            {
                title: 'Charges',
                href: chargesIndex(),
            },
            {
                title: 'Edit',
                href: chargesIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit Charge ${charge.period_label}`" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            :title="periodTitle"
            description="Edit Fee Type lines until a confirmed Payment freezes this Charge."
        />

        <p
            v-if="charge.is_frozen"
            class="text-muted-foreground text-sm"
        >
            This Charge is frozen after a confirmed Payment.
        </p>

        <form class="max-w-2xl space-y-6" @submit.prevent="submit">
            <div
                v-for="(line, index) in lines"
                :key="line.id ?? `new-${index}`"
                class="grid gap-4 border-b pb-4 sm:grid-cols-[1fr_8rem_auto]"
            >
                <div class="grid gap-2">
                    <Label :for="`fee_type_${index}`">Fee Type</Label>
                    <select
                        :id="`fee_type_${index}`"
                        :disabled="charge.is_frozen"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none disabled:opacity-50"
                        :value="line.fee_type_id ?? ''"
                        @change="
                            onFeeTypeChange(
                                index,
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                    >
                        <option
                            v-for="feeType in feeTypes"
                            :key="feeType.id"
                            :value="feeType.id"
                        >
                            {{ feeType.name }}
                        </option>
                    </select>
                </div>

                <div class="grid gap-2">
                    <Label :for="`amount_${index}`">Amount</Label>
                    <Input
                        :id="`amount_${index}`"
                        type="number"
                        min="0"
                        step="0.01"
                        :disabled="charge.is_frozen"
                        :model-value="line.amount"
                        @update:model-value="
                            (value) => (line.amount = String(value ?? ''))
                        "
                    />
                    <InputError :message="errors[`lines.${index}.amount`]" />
                </div>

                <div class="flex items-end">
                    <Button
                        type="button"
                        variant="ghost"
                        :disabled="charge.is_frozen"
                        @click="removeLine(index)"
                    >
                        Remove
                    </Button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="charge.is_frozen"
                    @click="addLine"
                >
                    Add line
                </Button>
                <Button
                    type="submit"
                    :disabled="processing || charge.is_frozen"
                >
                    Save changes
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="chargesIndex()">Back</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
