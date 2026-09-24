<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import OfficerPropertyInvitationController from '@/actions/App/Http/Controllers/Officer/PropertyInvitationController';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { propertyInvitationColumns } from '@/pages/officer/property-invitations/columns';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as propertyInvitationsIndex } from '@/routes/officer/property-invitations';
import type { DataTableValues } from '@/types/data-table';
import type {
    PropertyInvitation,
    PropertyInvitationPropertyOption,
} from '@/types/property-invitation';

type Props = {
    invitations: PropertyInvitation[];
    properties: PropertyInvitationPropertyOption[];
    table: {
        dateRanges: string[];
        values: DataTableValues;
    };
};

defineProps<Props>();

type InvitationForm = {
    property_id: number | '';
    role: 'owner' | 'resident';
};

type CreatedInvitationResponse = {
    url: string;
};

const invitationForm = useHttp<InvitationForm, CreatedInvitationResponse>(
    OfficerPropertyInvitationController.store(),
    {
        property_id: '',
        role: 'owner',
    },
);
const issuedInvitationUrl = ref<string | null>(null);
const copyLabel = ref('Copy link');

async function createInvitation(): Promise<void> {
    const response = await invitationForm.submit();

    if (!response) {
        return;
    }

    issuedInvitationUrl.value = response.url;
    copyLabel.value = 'Copy link';
    invitationForm.reset();
    router.reload({ only: ['invitations'] });
}

async function copyInvitationLink(): Promise<void> {
    if (!issuedInvitationUrl.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(issuedInvitationUrl.value);
        copyLabel.value = 'Copied';
    } catch {
        copyLabel.value = 'Copy failed';
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
            {
                title: 'Property Invitations',
                href: propertyInvitationsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Property Invitations" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Property Invitations"
            description="Issue secure, role-bound links for active Properties and review their status."
        />

        <Alert v-if="issuedInvitationUrl" variant="success">
            <AlertTitle>Invitation created</AlertTitle>
            <AlertDescription class="space-y-3">
                <p>
                    Copy this link now. It is shown only once; replace the
                    invitation if the link is lost.
                </p>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <Input
                        :model-value="issuedInvitationUrl"
                        readonly
                        class="font-mono text-xs"
                        @focus="($event.target as HTMLInputElement).select()"
                    />
                    <Button
                        type="button"
                        variant="outline"
                        @click="copyInvitationLink"
                    >
                        {{ copyLabel }}
                    </Button>
                </div>
            </AlertDescription>
        </Alert>

        <Card>
            <CardHeader>
                <CardTitle>Create an invitation</CardTitle>
                <CardDescription>
                    Choose an active Property and the Membership role this
                    one-use link will grant. Links expire after 30 days.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form
                    class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,14rem)_auto] lg:items-end"
                    @submit.prevent="createInvitation"
                >
                    <div class="grid gap-2">
                        <Label for="property_id">Property</Label>
                        <select
                            id="property_id"
                            name="property_id"
                            v-model="invitationForm.property_id"
                            required
                            :disabled="properties.length === 0"
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="" selected disabled>
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
                        <InputError
                            :message="invitationForm.errors.property_id"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="role">Role</Label>
                        <select
                            id="role"
                            name="role"
                            v-model="invitationForm.role"
                            required
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <option value="owner">Owner</option>
                            <option value="resident">Resident</option>
                        </select>
                        <InputError :message="invitationForm.errors.role" />
                    </div>

                    <Button
                        type="submit"
                        :disabled="
                            invitationForm.processing || properties.length === 0
                        "
                    >
                        Create invitation
                    </Button>
                </form>

                <p
                    v-if="properties.length === 0"
                    class="text-muted-foreground mt-3 text-sm"
                >
                    Add or reactivate a Property before creating an invitation.
                </p>
            </CardContent>
        </Card>

        <DataTable
            :columns="propertyInvitationColumns"
            :data="invitations"
            :action="propertyInvitationsIndex.url()"
            :date-ranges="table.dateRanges"
            :values="table.values"
            :date-range-labels="{
                created: 'Created',
                expires: 'Expires',
            }"
            empty-text="No Property Invitations yet."
        />
    </div>
</template>
