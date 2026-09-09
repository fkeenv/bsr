<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import LevySettingsController from '@/actions/App/Http/Controllers/Officer/LevySettingsController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import { edit as levySettingsEdit } from '@/routes/officer/levy-settings';

type Props = {
    levyDayOfMonth: number;
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
                title: 'Levy day',
                href: levySettingsEdit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Levy day" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Levy day"
            description="Day of the month Charges are generated automatically (Asia/Manila). Missing days run on the last day of the month."
        />

        <Form
            v-bind="LevySettingsController.update.form()"
            class="max-w-lg space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="levy_day_of_month">Day of month</Label>
                <Input
                    id="levy_day_of_month"
                    name="levy_day_of_month"
                    type="number"
                    min="1"
                    max="31"
                    required
                    :default-value="props.levyDayOfMonth"
                />
                <InputError :message="errors.levy_day_of_month" />
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">Save</Button>
                <Button variant="ghost" as-child>
                    <Link :href="officerDashboard()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
