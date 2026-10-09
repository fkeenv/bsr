<script setup lang="ts">
import { Form, useHttp } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import OfficerPropertyInvitationController from '@/actions/App/Http/Controllers/Officer/PropertyInvitationController';
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
                        toast.error('Invitation could not be copied', {
                            description: feedback.value,
                            duration: 10000,
                            closeButton: true,
                        });
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
                toast.success(`Invitation ${label} copied`, {
                    description:
                        'You can now paste it into a message to share the invitation.',
                    duration: 10000,
                    closeButton: true,
                });
            }
        } catch {
            if (generation === currentGeneration) {
                feedback.value =
                    'Copy failed — select and copy the shown value.';
                toast.error('Invitation could not be copied', {
                    description:
                        'Please select and copy the value shown in the invitation field.',
                    duration: 10000,
                    closeButton: true,
                });
            }
        }
    } catch {
        if (generation === currentGeneration) credentials.value = null;
        if (generation === currentGeneration && !feedback.value) {
            feedback.value =
                'Unable to retrieve this invitation. Refresh the table and try again.';
            toast.error('Invitation could not be copied', {
                description: feedback.value,
                duration: 10000,
                closeButton: true,
            });
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
                <p role="status" class="min-h-10 text-sm">
                    {{ feedback }}
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
