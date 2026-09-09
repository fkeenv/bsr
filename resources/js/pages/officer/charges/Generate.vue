<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ChargeGenerationController from '@/actions/App/Http/Controllers/Officer/ChargeGenerationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import { create as generateCharges } from '@/routes/officer/charges/generate';

type Props = {
    levyDayOfMonth: number;
    defaultYear: number;
    defaultMonth: number;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
            {
                title: 'Generate Charges',
                href: generateCharges(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Generate Charges" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Generate Charges"
            description="Create missing Charges for a Billing Period. Existing Charges are left alone."
        />

        <p class="text-muted-foreground text-sm">
            Scheduled levy day is day {{ levyDayOfMonth }} of each month
            (Asia/Manila).
        </p>

        <Form
            v-bind="ChargeGenerationController.store.form()"
            class="max-w-lg space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="year">Year</Label>
                    <Input
                        id="year"
                        name="year"
                        type="number"
                        required
                        :default-value="props.defaultYear"
                    />
                    <InputError :message="errors.year" />
                </div>
                <div class="grid gap-2">
                    <Label for="month">Month</Label>
                    <Input
                        id="month"
                        name="month"
                        type="number"
                        min="1"
                        max="12"
                        required
                        :default-value="props.defaultMonth"
                    />
                    <InputError :message="errors.month" />
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">
                    Generate Charges
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="officerDashboard()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
