<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    store,
    rename,
    destroy,
} from '@/actions/App/Http/Controllers/SuperAdmin/RolePermissionController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogClose,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RolePermission } from '@/types/role-permission';

const props = defineProps<{ role?: RolePermission; disabled?: boolean }>();
const open = ref(false);
const form = useForm({ name: props.role?.label ?? '' });
const deletion = useForm<{ role?: string }>({});
watch(open, (isOpen) => {
    if (isOpen) {
        form.defaults({ name: props.role?.label ?? '' });
        form.resetAndClearErrors();
        deletion.clearErrors();
    }
});
function save() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };
    if (props.role) form.patch(rename.url(props.role.name), options);
    else form.post(store.url(), options);
}
function remove() {
    if (!props.role || props.role.is_builtin || props.role.assigned_count > 0)
        return;
    deletion.delete(destroy.url(props.role.name), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                type="button"
                :variant="role ? 'outline' : 'default'"
                :size="role ? 'sm' : 'default'"
                :disabled="disabled"
                >{{ role ? 'Manage' : 'Create staff role' }}</Button
            >
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{
                    role ? 'Manage ' + role.label : 'Create staff role'
                }}</DialogTitle>
                <DialogDescription>{{
                    role
                        ? 'Renaming keeps this role’s permissions and assignments.'
                        : 'New roles start with no permissions. Choose the actions they can perform after creating the role.'
                }}</DialogDescription>
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label :for="'staff_role_name-' + (role?.name ?? 'new')"
                        >Role name</Label
                    >
                    <Input
                        :id="'staff_role_name-' + (role?.name ?? 'new')"
                        v-model="form.name"
                        placeholder="e.g. Community Treasurer"
                        required
                        maxlength="64"
                        :disabled="form.processing || deletion.processing"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <DialogFooter>
                    <DialogClose as-child
                        ><Button
                            type="button"
                            variant="secondary"
                            :disabled="form.processing || deletion.processing"
                            >Cancel</Button
                        ></DialogClose
                    >
                    <Button
                        type="submit"
                        :disabled="form.processing || deletion.processing"
                        >{{
                            form.processing
                                ? 'Saving…'
                                : role
                                  ? 'Save name'
                                  : 'Create role'
                        }}</Button
                    >
                </DialogFooter>
            </form>
            <div v-if="role" class="space-y-3 border-t pt-4">
                <p class="text-muted-foreground text-sm">
                    {{ role.assigned_count }} User Account{{
                        role.assigned_count === 1 ? '' : 's'
                    }}
                    assigned.
                </p>
                <p class="text-sm">
                    Deleting {{ role.label }} permanently removes its definition
                    and permissions. Roles with assignments cannot be deleted.
                </p>
                <InputError :message="deletion.errors.role" />
                <Button
                    type="button"
                    variant="destructive"
                    :disabled="
                        role.is_builtin ||
                        role.assigned_count > 0 ||
                        deletion.processing ||
                        form.processing
                    "
                    @click="remove"
                    >{{
                        deletion.processing
                            ? 'Deleting…'
                            : 'Delete ' + role.label
                    }}</Button
                >
            </div>
        </DialogContent>
    </Dialog>
</template>
