<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import FeeTypeController from '@/actions/App/Http/Controllers/Officer/FeeTypeController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createFeeType,
    index as feeTypesIndex,
} from '@/routes/officer/fee-types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
            {
                title: 'Fee Types',
                href: feeTypesIndex(),
            },
            {
                title: 'Add',
                href: createFeeType(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Add Fee Type" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Add Fee Type"
            description="Create a named dues Fee Type with its schedule amount."
        />

        <Form
            v-bind="FeeTypeController.store.form()"
            class="max-w-lg space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" required />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="amount">Amount</Label>
                <Input
                    id="amount"
                    name="amount"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                />
                <InputError :message="errors.amount" />
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">
                    Create Fee Type
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="feeTypesIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
