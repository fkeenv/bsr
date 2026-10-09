<script setup lang="ts">
import { Form, useHttp } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import OfficerPropertyInvitationController from '@/actions/App/Http/Controllers/Officer/PropertyInvitationController';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { PropertyInvitation } from '@/types/property-invitation';

const props = defineProps<{
    invitation: PropertyInvitation;
}>();

const open = ref(false);
type Credentials = { url: string; code: string };
const request = useHttp<Record<string, never>, Credentials>({});
const credentials = ref<Credentials | null>(null);
const feedback = ref('');
const copySucceeded = ref(false);
const pending = ref(false);
let generation = 0;
const shareOptions = [
    { key: 'url', label: 'Invitation link', button: 'Copy link' },
    { key: 'code', label: 'Invitation code', button: 'Copy code' },
] as const;

function clearSharing(): void {
    generation++;
    request.cancel();
    request.response = null;
    credentials.value = null;
    feedback.value = '';
    copySucceeded.value = false;
    pending.value = false;
}

watch(open, clearSharing);
watch(() => props.invitation, clearSharing);
onBeforeUnmount(clearSharing);

async function copyCredential(key: 'url' | 'code'): Promise<void> {
    if (pending.value || !open.value || !props.invitation.can_share) return;
    const currentGeneration = generation;
    pending.value = true;
    feedback.value = '';
    copySucceeded.value = false;
    try {
        const response = await request.get(
            OfficerPropertyInvitationController.share.url(props.invitation.id),
            {
                onError: (errors) => {
                    if (generation === currentGeneration) {
                        credentials.value = null;
                        feedback.value =
                            typeof errors.invitation === 'string'
                                ? errors.invitation
                                : 'Unable to retrieve this invitation. Refresh the table and try again.';
                    }
                },
            },
        );
        if (generation !== currentGeneration || !response) return;
        credentials.value = response;
        try {
            await navigator.clipboard.writeText(response[key]);
            if (generation === currentGeneration) {
                const label = key === 'url' ? 'link' : 'code';
                feedback.value = `Copied invitation ${label}. You can now paste it into a message.`;
                copySucceeded.value = true;
            }
        } catch {
            if (generation === currentGeneration) {
                feedback.value =
                    'Copy failed — select and copy the shown value.';
            }
        }
    } catch {
        if (generation === currentGeneration) credentials.value = null;
        if (generation === currentGeneration && !feedback.value) {
            feedback.value =
                'Unable to retrieve this invitation. Refresh the table and try again.';
        }
    } finally {
        if (generation === currentGeneration) pending.value = false;
    }
}

function closeDialog(): void {
    open.value = false;
}
</script>

<template>
    <div class="flex justify-end">
        <Dialog v-if="invitation.can_revoke" v-model:open="open">
            <DialogTrigger as-child>
                <Button variant="outline" size="sm">Manage</Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Manage invitation</DialogTitle>
                    <DialogDescription>
                        {{ invitation.property_label }} ·
                        <span class="capitalize">{{ invitation.role }}</span>
                    </DialogDescription>
                </DialogHeader>

                <div class="min-h-20">
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
                        >
                            {{ feedback }}
                        </AlertDescription>
                    </Alert>
                </div>

                <div v-if="invitation.can_share" class="space-y-3">
                    <div
                        v-for="option in shareOptions"
                        :key="option.key"
                        class="space-y-2"
                    >
                        <Label :for="`share-${option.key}-${invitation.id}`">{{
                            option.label
                        }}</Label>
                        <Input
                            :id="`share-${option.key}-${invitation.id}`"
                            :model-value="credentials?.[option.key] ?? ''"
                            placeholder="Use Copy to retrieve this value"
                            :disabled="pending || !credentials"
                            readonly
                            @focus="
                                ($event.target as HTMLInputElement).select()
                            "
                        />
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="pending"
                            @click="copyCredential(option.key)"
                            >{{ option.button }}</Button
                        >
                    </div>
                    <p class="text-muted-foreground text-sm">
                        Accepting the link or code uses up both.
                    </p>
                </div>
                <p v-else class="text-muted-foreground text-sm">
                    {{ invitation.sharing_unavailable_reason }}
                </p>

                <p class="text-muted-foreground text-sm">
                    Revoking this invitation permanently disables its link and
                    code.
                </p>

                <Form
                    v-bind="
                        OfficerPropertyInvitationController.destroy.form(
                            invitation.id,
                        )
                    "
                    :options="{ preserveScroll: true }"
                    @success="closeDialog"
                    v-slot="{ processing }"
                >
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing || pending"
                        >
                            Revoke invitation
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
