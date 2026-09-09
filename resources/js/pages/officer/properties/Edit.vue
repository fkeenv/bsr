<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PropertyController from '@/actions/App/Http/Controllers/Officer/PropertyController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as propertiesIndex } from '@/routes/officer/properties';
import type { Property } from '@/types/property';

type Props = {
    property: Property;
};

const { property } = defineProps<Props>();

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
                title: 'Edit',
                href: propertiesIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit Block ${property.block} Lot ${property.lot}`" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            :title="`Block ${property.block} · Lot ${property.lot}`"
            description="Block and Lot are immutable. Update address, recorded owner, and Opening Balance when still editable."
        />

        <Form
            v-bind="PropertyController.update.form(property)"
            class="max-w-lg space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="street_address">Street address</Label>
                <Input
                    id="street_address"
                    name="street_address"
                    :default-value="property.street_address ?? ''"
                />
                <InputError :message="errors.street_address" />
            </div>

            <div class="grid gap-2">
                <Label for="recorded_owner_name">Recorded owner</Label>
                <Input
                    id="recorded_owner_name"
                    name="recorded_owner_name"
                    :default-value="property.recorded_owner_name ?? ''"
                />
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
                    :default-value="property.opening_balance"
                    :disabled="property.opening_balance_is_frozen"
                />
                <p
                    v-if="property.opening_balance_is_frozen"
                    class="text-muted-foreground text-sm"
                >
                    Opening Balance is frozen after a confirmed Payment.
                </p>
                <InputError :message="errors.opening_balance" />
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">
                    Save changes
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="propertiesIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
