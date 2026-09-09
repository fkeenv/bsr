<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import SuspendController from '@/actions/App/Http/Controllers/Officer/SuspendController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createSuspend,
    index as suspendsIndex,
} from '@/routes/officer/suspends';
import type { FeeType } from '@/types/fee-type';
import type { PropertyOption } from '@/types/suspend';

type Props = {
    properties: PropertyOption[];
    feeTypes: FeeType[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
            {
                title: 'Suspends',
                href: suspendsIndex(),
            },
            {
                title: 'Add',
                href: createSuspend(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Add Suspend" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Add Suspend"
            description="Omit a Fee Type from a Property’s Charges for covered Billing Periods."
        />

        <Form
            v-bind="SuspendController.store.form()"
            class="max-w-lg space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="property_id">Property</Label>
                <select
                    id="property_id"
                    name="property_id"
                    required
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                >
                    <option value="" disabled selected>Select Property</option>
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
                <Label for="fee_type_id">Fee Type</Label>
                <select
                    id="fee_type_id"
                    name="fee_type_id"
                    required
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                >
                    <option value="" disabled selected>Select Fee Type</option>
                    <option
                        v-for="feeType in feeTypes"
                        :key="feeType.id"
                        :value="feeType.id"
                    >
                        {{ feeType.name }}
                    </option>
                </select>
                <InputError :message="errors.fee_type_id" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="starts_year">Starts year</Label>
                    <Input
                        id="starts_year"
                        name="starts_year"
                        type="number"
                        required
                    />
                    <InputError :message="errors.starts_year" />
                </div>
                <div class="grid gap-2">
                    <Label for="starts_month">Starts month</Label>
                    <Input
                        id="starts_month"
                        name="starts_month"
                        type="number"
                        min="1"
                        max="12"
                        required
                    />
                    <InputError :message="errors.starts_month" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="ends_year">Ends year (optional)</Label>
                    <Input id="ends_year" name="ends_year" type="number" />
                    <InputError :message="errors.ends_year" />
                </div>
                <div class="grid gap-2">
                    <Label for="ends_month">Ends month (optional)</Label>
                    <Input
                        id="ends_month"
                        name="ends_month"
                        type="number"
                        min="1"
                        max="12"
                    />
                    <InputError :message="errors.ends_month" />
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">
                    Create Suspend
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="suspendsIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
