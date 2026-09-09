<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PropertyController from '@/actions/App/Http/Controllers/Officer/PropertyController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createProperty,
    index as propertiesIndex,
} from '@/routes/officer/properties';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
            {
                title: 'Properties',
                href: propertiesIndex(),
            },
            {
                title: 'Add',
                href: createProperty(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Add Property" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Add Property"
            description="Create a roster house identified by Block and Lot."
        />

        <Form
            v-bind="PropertyController.store.form()"
            class="max-w-lg space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="block">Block</Label>
                    <Input id="block" name="block" required />
                    <InputError :message="errors.block" />
                </div>
                <div class="grid gap-2">
                    <Label for="lot">Lot</Label>
                    <Input id="lot" name="lot" required />
                    <InputError :message="errors.lot" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="street_address">Street address</Label>
                <Input id="street_address" name="street_address" />
                <InputError :message="errors.street_address" />
            </div>

            <div class="grid gap-2">
                <Label for="recorded_owner_name">Recorded owner</Label>
                <Input id="recorded_owner_name" name="recorded_owner_name" />
                <InputError :message="errors.recorded_owner_name" />
            </div>

            <div class="grid gap-2">
                <Label for="opening_balance">Opening Balance</Label>
                <Input
                    id="opening_balance"
                    name="opening_balance"
                    type="number"
                    min="0"
                    step="0.01"
                    default-value="0"
                />
                <InputError :message="errors.opening_balance" />
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">
                    Create Property
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="propertiesIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
