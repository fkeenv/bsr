<script setup lang="ts">
import { setLayoutProps, Form, Head, Link } from '@inertiajs/vue3';
import OfficerPaymentController from '@/actions/App/Http/Controllers/Officer/PaymentController';
import Heading from '@/components/Heading.vue';
import { useStaffPermissions } from '@/composables/useStaffPermissions';
import InputError from '@/components/InputError.vue';
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

const { allows } = useStaffPermissions();
setLayoutProps({
    breadcrumbs: [
        {
            title: 'Officer',
            href: officerDashboard(),
        },
        {
            title: 'Payments',
            href: allows('payments.read')
                ? paymentsIndex()
                : officerDashboard(),
        },
        {
            title: 'Record',
            href: createPayment(),
        },
    ],
});
</script>

<template>
    <div>
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
                    <Select name="property_id" required>
                        <SelectTrigger id="property_id" class="w-full"
                            ><SelectValue placeholder="Select a Property"
                        /></SelectTrigger>
                        <SelectContent
                            ><SelectItem
                                v-for="property in properties"
                                :key="property.id"
                                :value="String(property.id)"
                                >{{ property.label }}</SelectItem
                            ></SelectContent
                        >
                    </Select>
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
                    <Select name="method" default-value="cash" required>
                        <SelectTrigger id="method" class="w-full"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="cash">Cash</SelectItem
                            ><SelectItem value="bank">Bank</SelectItem>
                            <SelectItem value="gcash">GCash</SelectItem
                            ><SelectItem value="maya">Maya</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.method" />
                </div>

                <div class="grid gap-2">
                    <Label for="reference">Reference</Label>
                    <Input id="reference" name="reference" />
                    <InputError :message="errors.reference" />
                </div>

                <div class="grid gap-2">
                    <Label for="screenshot">Receipt screenshot</Label>
                    <Input
                        id="screenshot"
                        name="screenshot"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        required
                    />
                    <InputError :message="errors.screenshot" />
                </div>

                <div class="flex gap-2">
                    <Button type="submit" :disabled="processing">
                        Confirm Payment
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link
                            :href="
                                allows('payments.read')
                                    ? paymentsIndex()
                                    : officerDashboard()
                            "
                            >Cancel</Link
                        >
                    </Button>
                </div>
            </Form>
        </div>
    </div>
</template>
