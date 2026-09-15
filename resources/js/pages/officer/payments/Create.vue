<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import OfficerPaymentController from '@/actions/App/Http/Controllers/Officer/PaymentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createPayment,
    index as paymentsIndex,
} from '@/routes/officer/payments';

type PropertyOption = {
    id: number;
    label: string;
};

defineProps<{
    properties: PropertyOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
            {
                title: 'Payments',
                href: paymentsIndex(),
            },
            {
                title: 'Record',
                href: createPayment(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Record Payment" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Record Payment"
            description="Create and confirm a Payment without a Member declaration. Allocation runs immediately."
        />

        <Form
            v-bind="OfficerPaymentController.store.form()"
            enctype="multipart/form-data"
            class="max-w-lg space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="property_id">Property</Label>
                <select
                    id="property_id"
                    name="property_id"
                    required
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                >
                    <option value="" disabled selected>
                        Select a Property
                    </option>
                    <option
                        v-for="property in properties"
                        :key="property.id"
                        :value="property.id"
                    >
                        {{ property.label }}
                    </option>
                </select>
                <InputError :message="errors.property_id" />
            </div>

            <div class="grid gap-2">
                <Label for="amount">Amount</Label>
                <Input
                    id="amount"
                    name="amount"
                    type="number"
                    min="0.01"
                    step="0.01"
                    required
                />
                <InputError :message="errors.amount" />
            </div>

            <div class="grid gap-2">
                <Label for="method">Method</Label>
                <select
                    id="method"
                    name="method"
                    required
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
                <Input id="reference" name="reference" />
                <InputError :message="errors.reference" />
            </div>

            <div class="grid gap-2">
                <Label for="screenshot">Receipt screenshot (optional)</Label>
                <Input
                    id="screenshot"
                    name="screenshot"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                />
                <InputError :message="errors.screenshot" />
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">
                    Confirm Payment
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="paymentsIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
