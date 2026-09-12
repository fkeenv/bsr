<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import MembershipController from '@/actions/App/Http/Controllers/MembershipController';
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
    <div class="flex justify-end">
        <Dialog v-model:open="open">
            <DialogTrigger as-child>
                <Button variant="outline" size="sm">End</Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-md">
                <Form
                    v-bind="MembershipController.end.form(membership.id)"
                    :options="{ preserveScroll: true }"
                    @success="closeDialog"
                    v-slot="{ processing }"
                >
                    <DialogHeader>
                        <DialogTitle>End Membership</DialogTitle>
                        <DialogDescription>
                            End your Membership
                            <template v-if="membership.property_label">
                                for {{ membership.property_label }}
                            </template>
                            ? You can apply again later if needed.
                        </DialogDescription>
                    </DialogHeader>

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
