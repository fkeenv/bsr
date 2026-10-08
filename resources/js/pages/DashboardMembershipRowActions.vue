<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import MembershipController from '@/actions/App/Http/Controllers/MembershipController';
import InputError from '@/components/InputError.vue';
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
import type { Membership } from '@/types/membership';

defineProps<{
    membership: Membership;
}>();

const open = ref(false);

function closeDialog(): void {
    open.value = false;
}
</script>

<template>
    <div class="flex justify-start sm:justify-end">
        <Dialog v-model:open="open">
            <DialogTrigger as-child>
                <Button
                    variant="ghost"
                    class="text-muted-foreground h-auto min-h-11 whitespace-normal"
                    :aria-label="`Manage Membership for ${membership.property_label || `Property ${membership.property_id}`}`"
                    >Manage Membership</Button
                >
            </DialogTrigger>
            <DialogContent class="sm:max-w-md">
                <Form
                    v-bind="MembershipController.end.form(membership.id)"
                    :options="{ preserveScroll: true }"
                    @success="closeDialog"
                    v-slot="{ processing, errors }"
                >
                    <DialogHeader>
                        <DialogTitle>End Membership</DialogTitle>
                        <DialogDescription>
                            End your Membership
                            <template v-if="membership.property_label">
                                for {{ membership.property_label }}
                            </template>
                            ? You can join again with a new Property Invitation.
                        </DialogDescription>
                    </DialogHeader>

                    <InputError :message="errors.membership" class="mt-4" />
                    <InputError :message="errors.end_reason" class="mt-4" />

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
                            End Membership
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
