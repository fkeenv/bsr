<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import OfficerPropertyInvitationController from '@/actions/App/Http/Controllers/Officer/PropertyInvitationController';
import { Button } from '@/components/ui/button';
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

defineProps<{
    invitation: PropertyInvitation;
}>();

const open = ref(false);

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

                <p class="text-muted-foreground text-sm">
                    Revoking this invitation permanently disables its link.
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
                            :disabled="processing"
                        >
                            Revoke invitation
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
