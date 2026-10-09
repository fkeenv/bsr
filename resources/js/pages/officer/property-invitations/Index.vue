<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import OfficerPropertyInvitationController from '@/actions/App/Http/Controllers/Officer/PropertyInvitationController';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PropertyInvitationPropertyPicker from '@/components/PropertyInvitationPropertyPicker.vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    code: string;
};

const invitationForm = useHttp<InvitationForm, CreatedInvitationResponse>(
    OfficerPropertyInvitationController.store(),
    {
        property_id: '',
        role: 'owner',
    },
);
const issuedInvitation = ref<CreatedInvitationResponse | null>(null);
const shareOptions = [
    { key: 'url', label: 'Invitation link', button: 'Copy link' },
    { key: 'code', label: 'Invitation code', button: 'Copy code' },
] as const;
const copyFeedback = ref<Partial<Record<'url' | 'code', string>>>({});

async function createInvitation(): Promise<void> {
    const response = await invitationForm.submit();
    if (!response) {
        return;
    }
    issuedInvitation.value = response;
    copyFeedback.value = {};
    invitationForm.defaults({ property_id: '', role: 'owner' });
    invitationForm.reset();
    router.reload({ only: ['invitations'] });
}

async function copyInvitationCredential(key: 'url' | 'code'): Promise<void> {
    if (!issuedInvitation.value) {
        return;
    }
    try {
        await navigator.clipboard.writeText(issuedInvitation.value[key]);
        copyFeedback.value[key] = 'Copied';
    } catch {
        copyFeedback.value[key] =
            'Copy failed — select and copy the shown value.';
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
            description="Issue secure, role-bound links and codes for active Properties and review their status."
        />

        <Alert v-if="issuedInvitation" variant="success">
            <AlertTitle>Invitation created</AlertTitle>
            <AlertDescription class="space-y-3">
                <p>
                    Share either the link or the code. Accepting one uses up
                    both. Copy these details before leaving this page.
                </p>
                <div
                    v-for="option in shareOptions"
                    :key="option.key"
                    class="grid gap-2"
                >
                    <Label :for="`issued-${option.key}`">{{
                        option.label
                    }}</Label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <Input
                            :id="`issued-${option.key}`"
                            :model-value="issuedInvitation[option.key]"
                            readonly
                            class="font-mono text-xs"
                            @focus="
                                ($event.target as HTMLInputElement).select()
                            "
                        />
                        <Button
                            type="button"
                            variant="outline"
                            @click="copyInvitationCredential(option.key)"
                            >{{ option.button }}</Button
                        >
                    </div>
                    <p v-if="copyFeedback[option.key]" role="status">
                        {{ copyFeedback[option.key] }}
                    </p>
                </div>
            </AlertDescription>
        </Alert>

        <Card>
            <CardHeader>
                <CardTitle>Create an invitation</CardTitle>
                <CardDescription>
                    Choose an active Property and the Membership role this
                    one-use invitation will grant. Its link and code expire
                    after 30 days.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form
                    class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,14rem)_auto] lg:items-end"
                    @submit.prevent="createInvitation"
                >
                    <div class="grid gap-2">
                        <Label for="property_id">Property</Label>
                        <PropertyInvitationPropertyPicker
                            id="property_id"
                            v-model="invitationForm.property_id"
                            :properties="properties"
                        />
                        <InputError
                            :message="invitationForm.errors.property_id"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="role">Role</Label>
                        <Select v-model="invitationForm.role">
                            <SelectTrigger id="role" class="w-full"
                                ><SelectValue
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="owner">Owner</SelectItem>
                                <SelectItem value="resident"
                                    >Resident</SelectItem
                                >
                            </SelectContent>
                        </Select>
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
