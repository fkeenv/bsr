<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    changeMembershipRole,
    endMembership,
} from '@/actions/App/Http/Controllers/Administrator/UserAccountController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { UserAccount, UserAccountMembership } from '@/types/user-account';
const props = defineProps<{
    account: UserAccount;
    membership: UserAccountMembership;
    canEnd: boolean;
    canChangeRole: boolean;
    losesStaffAccess: boolean;
}>();
const open = ref(false);
const operation = ref('role');
const form = useForm({
    role: props.membership.role,
    end_reason: '',
    confirm_staff_access_loss: false,
});
const needsConfirmation = computed(
    () =>
        props.losesStaffAccess &&
        (operation.value === 'end' || form.role === 'resident'),
);
watch(open, (value) => {
    if (value) {
        operation.value = props.canChangeRole ? 'role' : 'end';
        form.defaults({
            role: props.membership.role,
            end_reason: '',
            confirm_staff_access_loss: false,
        });
        form.resetAndClearErrors();
    }
});
watch([operation, () => form.role], () => {
    form.confirm_staff_access_loss = false;
});
function submit() {
    if (
        form.processing ||
        (needsConfirmation.value && !form.confirm_staff_access_loss)
    )
        return;
    const args = { user: props.account.id, membership: props.membership.id };
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };
    if (operation.value === 'end' && props.canEnd)
        form.post(endMembership.url(args), options);
    else if (operation.value === 'role' && props.canChangeRole)
        form.put(changeMembershipRole.url(args), options);
}
</script>
<template>
    <Dialog v-if="canEnd || canChangeRole" v-model:open="open">
        <DialogTrigger as-child
            ><Button variant="outline" size="sm">Manage</Button></DialogTrigger
        >
        <DialogContent class="sm:max-w-lg">
            <DialogHeader
                ><DialogTitle>Manage Property Membership</DialogTitle
                ><DialogDescription
                    >{{ account.name }} · {{ membership.property_label }} ·
                    {{
                        membership.role === 'owner' ? 'Owner' : 'Resident'
                    }}</DialogDescription
                ></DialogHeader
            >
            <form class="space-y-4" @submit.prevent="submit">
                <div v-if="canEnd && canChangeRole" class="grid gap-2">
                    <Label :for="`operation-${membership.id}`">Change</Label>
                    <Select v-model="operation"
                        ><SelectTrigger :id="`operation-${membership.id}`"
                            ><SelectValue /></SelectTrigger
                        ><SelectContent
                            ><SelectItem value="role"
                                >Change Owner / Resident role</SelectItem
                            ><SelectItem value="end"
                                >End this Membership</SelectItem
                            ></SelectContent
                        ></Select
                    >
                </div>
                <div v-if="operation === 'role'" class="grid gap-2">
                    <Label :for="`property-role-${membership.id}`"
                        >Property Membership role</Label
                    >
                    <Select v-model="form.role"
                        ><SelectTrigger :id="`property-role-${membership.id}`"
                            ><SelectValue /></SelectTrigger
                        ><SelectContent
                            ><SelectItem value="owner">Owner</SelectItem
                            ><SelectItem value="resident"
                                >Resident</SelectItem
                            ></SelectContent
                        ></Select
                    >
                    <InputError :message="form.errors.role" />
                </div>
                <div v-else class="grid gap-2">
                    <Label :for="`end-reason-${membership.id}`"
                        >Reason for ending this Membership</Label
                    >
                    <Textarea
                        :id="`end-reason-${membership.id}`"
                        v-model="form.end_reason"
                        required
                        maxlength="5000"
                    />
                    <InputError :message="form.errors.end_reason" />
                </div>
                <Alert
                    v-if="needsConfirmation"
                    class="border-amber-500/40 bg-amber-500/10"
                >
                    <AlertTitle>All staff access will be removed</AlertTitle>
                    <AlertDescription
                        >This is the last live Owner Membership.
                        {{ account.name }} will lose these staff roles:
                        {{ account.staff_roles.join(', ') }}. Restoring
                        ownership will require staff
                        reappointment.</AlertDescription
                    >
                </Alert>
                <div v-if="needsConfirmation" class="flex items-start gap-2">
                    <Checkbox
                        :id="`staff-loss-${membership.id}`"
                        v-model="form.confirm_staff_access_loss"
                    />
                    <Label
                        :for="`staff-loss-${membership.id}`"
                        class="leading-relaxed"
                        >I understand that this removes all staff roles and
                        staff access.</Label
                    >
                </div>
                <InputError :message="form.errors.confirm_staff_access_loss" />
                <InputError
                    :message="
                        (form.errors as Record<string, string>).membership
                    "
                />
                <DialogFooter
                    ><DialogClose as-child
                        ><Button type="button" variant="secondary"
                            >Cancel</Button
                        ></DialogClose
                    ><Button
                        :variant="
                            operation === 'end' ? 'destructive' : 'default'
                        "
                        :disabled="
                            form.processing ||
                            (needsConfirmation &&
                                !form.confirm_staff_access_loss)
                        "
                        >{{
                            operation === 'end'
                                ? 'End Membership'
                                : 'Update Property role'
                        }}</Button
                    ></DialogFooter
                >
            </form>
        </DialogContent>
    </Dialog>
</template>
