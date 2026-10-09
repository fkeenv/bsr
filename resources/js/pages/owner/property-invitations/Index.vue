<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import OwnerPropertyInvitationController from '@/actions/App/Http/Controllers/Owner/PropertyInvitationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import OwnerInvitationRowActions from './OwnerInvitationRowActions.vue';
import { dashboard } from '@/routes';
import type { PropertyInvitation } from '@/types/property-invitation';

const props = defineProps<{
    property: { id: number; label: string; is_active: boolean };
    invitations: PropertyInvitation[];
}>();

type Credentials = { url: string; code: string };
const form = useHttp<Record<string, never>, Credentials>({});
const creationError = ref('');
const issued = ref<Credentials | null>(null);
const feedback = ref('');
const copySucceeded = ref(false);
const shareOptions = [
    { key: 'url', label: 'Invitation link', button: 'Copy link' },
    { key: 'code', label: 'Invitation code', button: 'Copy code' },
] as const;

async function createInvitation(): Promise<void> {
    if (form.processing || !props.property.is_active) return;
    creationError.value = '';
    try {
        const response = await form.post(
            OwnerPropertyInvitationController.store.url(props.property.id),
        );
        if (!response) return;
        issued.value = response;
        feedback.value = '';
        router.reload({ only: ['invitations'] });
    } catch {
        creationError.value =
            'Unable to create an invitation. Refresh the page to check your Property access and try again.';
    }
}

async function copyCredential(key: 'url' | 'code'): Promise<void> {
    if (!issued.value) return;
    try {
        await navigator.clipboard.writeText(issued.value[key]);
        feedback.value = `Copied invitation ${key === 'url' ? 'link' : 'code'}. You can now paste it into a message.`;
        copySucceeded.value = true;
    } catch {
        feedback.value = 'Copy failed — select and copy the shown value.';
        copySucceeded.value = false;
    }
}

function displayDate(value: string): string {
    return new Intl.DateTimeFormat('en-PH', {
        dateStyle: 'medium',
        timeZone: 'Asia/Manila',
    }).format(new Date(value));
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Your Properties', href: dashboard() },
            { title: 'Resident invitations' },
        ],
    },
});
</script>

<template>
    <div class="space-y-6 p-4 md:p-6">
        <Head title="Resident invitations" />
        <Heading
            :title="`Resident invitations · ${property.label}`"
            description="Invite a renter or another resident to this Property. They will review the invitation and accept the association’s legal documents before joining."
        />
        <Card>
            <CardHeader>
                <CardTitle>Invite a resident</CardTitle>
                <CardDescription
                    >This invitation gives a resident Membership for
                    {{ property.label }} and expires in 30
                    days.</CardDescription
                >
            </CardHeader>
            <CardContent class="space-y-4">
                <form @submit.prevent="createInvitation">
                    <Button
                        type="submit"
                        :disabled="form.processing || !property.is_active"
                        >{{
                            form.processing
                                ? 'Creating invitation…'
                                : 'Create resident invitation'
                        }}</Button
                    >
                    <InputError :message="form.errors.role" />
                    <InputError :message="form.errors.property_id" />
                    <InputError :message="creationError" />
                    <p
                        v-if="!property.is_active"
                        class="text-muted-foreground mt-2 text-sm"
                    >
                        This Property is inactive. You can review or revoke
                        existing invitations, but cannot create new ones.
                    </p>
                </form>
                <Alert v-if="issued" class="space-y-3">
                    <AlertTitle>Invitation ready to share</AlertTitle>
                    <AlertDescription class="space-y-3">
                        <p>
                            Share either the link or the code. Accepting one
                            uses up both. Copy these details before leaving this
                            page.
                        </p>
                        <Alert
                            v-if="feedback"
                            role="status"
                            :class="
                                copySucceeded
                                    ? 'border-green-200 bg-green-50 text-green-950 dark:border-green-800 dark:bg-green-950 dark:text-green-100'
                                    : 'border-red-200 bg-red-50 text-red-950 dark:border-red-800 dark:bg-red-950 dark:text-red-100'
                            "
                        >
                            <AlertDescription
                                class="text-base font-medium text-current"
                                >{{ feedback }}</AlertDescription
                            >
                        </Alert>
                        <div
                            v-for="option in shareOptions"
                            :key="option.key"
                            class="space-y-2"
                        >
                            <Label :for="`owner-issued-${option.key}`">{{
                                option.label
                            }}</Label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <Input
                                    :id="`owner-issued-${option.key}`"
                                    :model-value="issued[option.key]"
                                    readonly
                                    @focus="
                                        (
                                            $event.target as HTMLInputElement
                                        ).select()
                                    "
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="copyCredential(option.key)"
                                    >{{ option.button }}</Button
                                >
                            </div>
                        </div>
                    </AlertDescription>
                </Alert>
            </CardContent>
        </Card>
        <Card>
            <CardHeader>
                <CardTitle>Your issued invitations</CardTitle>
                <CardDescription
                    >Only invitations you issued for {{ property.label }} are
                    shown here. Officers can review and revoke them
                    too.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <p
                    v-if="invitations.length === 0"
                    class="text-muted-foreground text-sm"
                >
                    You haven’t issued any invitations for this Property yet.
                </p>
                <Table v-else>
                    <TableHeader
                        ><TableRow
                            ><TableHead>Created</TableHead
                            ><TableHead>Expires</TableHead
                            ><TableHead>Status</TableHead
                            ><TableHead class="text-right"
                                >Actions</TableHead
                            ></TableRow
                        ></TableHeader
                    >
                    <TableBody>
                        <TableRow
                            v-for="invitation in invitations"
                            :key="invitation.id"
                        >
                            <TableCell>{{
                                displayDate(invitation.created_at)
                            }}</TableCell>
                            <TableCell>{{
                                displayDate(invitation.expires_at)
                            }}</TableCell>
                            <TableCell
                                ><Badge
                                    variant="secondary"
                                    class="capitalize"
                                    >{{ invitation.status }}</Badge
                                ></TableCell
                            >
                            <TableCell
                                ><OwnerInvitationRowActions
                                    :invitation="invitation"
                            /></TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>
</template>
