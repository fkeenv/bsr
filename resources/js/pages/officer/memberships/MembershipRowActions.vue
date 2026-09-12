<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import OfficerMembershipController from '@/actions/App/Http/Controllers/Officer/MembershipController';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Membership } from '@/types/membership';

const props = defineProps<{
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
                <Button variant="outline" size="sm">Manage</Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Manage Membership</DialogTitle>
                    <DialogDescription>
                        {{ props.membership.user_name || 'Member' }}
                        <template v-if="props.membership.property_label">
                            · {{ props.membership.property_label }}
                        </template>
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-6">
                    <Form
                        v-bind="
                            OfficerMembershipController.updateRole.form(
                                props.membership.id,
                            )
                        "
                        class="space-y-4"
                        :options="{ preserveScroll: true }"
                        @success="closeDialog"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label :for="`role_${props.membership.id}`"
                                >Role</Label
                            >
                            <select
                                :id="`role_${props.membership.id}`"
                                name="role"
                                required
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                            >
                                <option
                                    value="owner"
                                    :selected="
                                        props.membership.role === 'owner'
                                    "
                                >
                                    Owner
                                </option>
                                <option
                                    value="resident"
                                    :selected="
                                        props.membership.role === 'resident'
                                    "
                                >
                                    Resident
                                </option>
                            </select>
                            <InputError :message="errors.role" />
                        </div>
                        <Button type="submit" :disabled="processing">
                            Update role
                        </Button>
                    </Form>

                    <div class="border-border border-t" />

                    <Form
                        v-bind="
                            OfficerMembershipController.end.form(
                                props.membership.id,
                            )
                        "
                        class="space-y-4"
                        :options="{ preserveScroll: true }"
                        @success="closeDialog"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label :for="`end_reason_${props.membership.id}`"
                                >End reason</Label
                            >
                            <Input
                                :id="`end_reason_${props.membership.id}`"
                                name="end_reason"
                                required
                            />
                            <InputError :message="errors.end_reason" />
                        </div>
                        <DialogFooter class="gap-2 sm:justify-between">
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
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
