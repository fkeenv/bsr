<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import SuspendController from '@/actions/App/Http/Controllers/Officer/SuspendController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as suspendsIndex } from '@/routes/officer/suspends';
import type { FeeType } from '@/types/fee-type';
import type { PropertyOption, Suspend } from '@/types/suspend';

type Props = {
    suspend: Suspend;
    properties: PropertyOption[];
    feeTypes: FeeType[];
};

const { suspend } = defineProps<Props>();

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
                title: 'Edit',
                href: suspendsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Edit Suspend" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Edit Suspend"
            description="Adjust which Billing Periods omit this Fee Type on the Property."
        />

        <Form
            v-bind="SuspendController.update.form(suspend)"
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
                    <option
                        v-for="property in properties"
                        :key="property.id"
                        :value="property.id"
                        :selected="property.id === suspend.property_id"
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
                    <option
                        v-for="feeType in feeTypes"
                        :key="feeType.id"
                        :value="feeType.id"
                        :selected="feeType.id === suspend.fee_type_id"
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
                        :default-value="suspend.starts_year"
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
                        :default-value="suspend.starts_month"
                    />
                    <InputError :message="errors.starts_month" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="ends_year">Ends year (optional)</Label>
                    <Input
                        id="ends_year"
                        name="ends_year"
                        type="number"
                        :default-value="suspend.ends_year ?? ''"
                    />
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
                        :default-value="suspend.ends_month ?? ''"
                    />
                    <InputError :message="errors.ends_month" />
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">
                    Save changes
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="suspendsIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
