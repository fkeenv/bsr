<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    assignRole,
    revokeRole,
} from '@/actions/App/Http/Controllers/Administrator/UserAccountController';
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
import type { StaffRoleOption, UserAccount } from '@/types/user-account';
const props = defineProps<{ account: UserAccount; role: StaffRoleOption }>();
const open = ref(false);
const form = useForm<{ user_id?: number }>({});
const eligible = () =>
    props.account.is_super_admin || props.account.has_owner_membership;
watch(open, () => form.clearErrors());
function submit() {
    if (
        !props.role.can_manage ||
        (!props.role.assigned && !eligible()) ||
        form.processing
    )
        return;
    const args = { user: props.account.id, role: props.role.name };
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };
    if (props.role.assigned) form.delete(revokeRole.url(args), options);
    else form.post(assignRole.url(args), options);
}
</script>
<template>
    <Dialog v-if="role.can_manage" v-model:open="open">
        <DialogTrigger as-child
            ><Button
                variant="outline"
                size="sm"
                :disabled="!role.assigned && !eligible()"
                >Manage</Button
            ></DialogTrigger
        >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle
                    >{{ role.assigned ? 'Revoke' : 'Assign' }}
                    {{ role.label }}</DialogTitle
                >
                <DialogDescription
                    >{{ account.name }} · {{ account.email }}</DialogDescription
                >
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="submit">
                <p class="text-sm">
                    {{
                        role.assigned
                            ? 'This removes only this staff role. Access from other assigned roles and Property Memberships remains.'
                            : 'This adds a staff role alongside existing assignments. Its permissions apply while the account is eligible.'
                    }}
                </p>
                <InputError :message="form.errors.user_id" />
                <DialogFooter>
                    <DialogClose as-child
                        ><Button type="button" variant="secondary"
                            >Cancel</Button
                        ></DialogClose
                    >
                    <Button
                        :variant="role.assigned ? 'destructive' : 'default'"
                        :disabled="form.processing"
                        >{{
                            role.assigned ? 'Revoke role' : 'Assign role'
                        }}</Button
                    >
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
