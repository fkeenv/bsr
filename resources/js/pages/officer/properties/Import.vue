<script setup lang="ts">
import { useStaffPermissions } from '@/composables/useStaffPermissions';
import { dashboard } from '@/routes';
import { Form, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import PropertyImportController from '@/actions/App/Http/Controllers/Officer/PropertyImportController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as propertiesIndex } from '@/routes/officer/properties';
import { create as importProperties } from '@/routes/officer/properties/import';

type Props = {
    canImport: boolean;
};

defineProps<Props>();

const { allows } = useStaffPermissions();

setLayoutProps({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        ...(allows('properties.read')
            ? [{ title: 'Properties', href: propertiesIndex() }]
            : []),
        { title: 'Import', href: importProperties() },
    ],
});
</script>

<template>
    <div class="flex flex-col space-y-6 p-4">
        <Head title="Import Property CSV" />
        <Heading
            title="Import Property CSV"
            description="Go-live upsert: creates unknown Block+Lot rows, updates address and recorded owner, applies Opening Balance only on create, and never deletes."
        />

        <div class="max-w-lg space-y-6">
            <p class="text-muted-foreground text-sm">
                Required columns: block, lot. Optional: street_address,
                recorded_owner_name, opening_balance.
                <a
                    v-if="allows('properties.import-sample')"
                    class="text-foreground underline underline-offset-4"
                    :href="PropertyImportController.sample.url()"
                >
                    Download sample CSV
                </a>
            </p>

            <Form
                v-if="canImport"
                v-bind="PropertyImportController.store.form()"
                enctype="multipart/form-data"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="csv">CSV file</Label>
                    <Input
                        id="csv"
                        name="csv"
                        type="file"
                        accept=".csv,text/csv"
                        required
                    />
                    <InputError :message="errors.csv" />
                </div>

                <div class="flex gap-2">
                    <Button type="submit" :disabled="processing">
                        Import roster
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link
                            :href="
                                allows('properties.read')
                                    ? propertiesIndex()
                                    : dashboard()
                            "
                            >Cancel</Link
                        >
                    </Button>
                </div>
            </Form>

            <div v-else class="flex gap-2">
                <Button variant="ghost" as-child>
                    <Link
                        :href="
                            allows('properties.read')
                                ? propertiesIndex()
                                : dashboard()
                        "
                        >Back to roster</Link
                    >
                </Button>
            </div>
        </div>
    </div>
</template>
