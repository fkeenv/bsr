<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Accordion } from '@/components/ui/accordion';
import RolePermissionEditor from './RolePermissionEditor.vue';
import StaffRoleDialog from './StaffRoleDialog.vue';
import { dashboard } from '@/routes/super-admin';
import { index } from '@/routes/super-admin/role-permissions';
import type { PermissionOption, RolePermission } from '@/types/role-permission';

defineProps<{ roles: RolePermission[]; permissions: PermissionOption[] }>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Super Admin', href: dashboard() },
            { title: 'Roles & permissions', href: index() },
        ],
    },
});
</script>

<template>
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 sm:p-8">
        <Head title="Roles & permissions" />
        <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row"
        >
            <Heading
                title="Roles & permissions"
                description="Choose the actions each staff role can perform. Changes apply on the next request, including current sessions."
            />
            <StaffRoleDialog />
        </div>
        <p class="text-muted-foreground text-sm leading-6">
            Super Admin always has full access. Member and User access is
            automatic. Financial actions follow these permissions while
            allocation and billing rules still apply. Each permission is
            independent; enabling an action does not enable other actions. Staff
            roles require a live Owner Membership, and multiple roles combine
            their permissions. Built-in roles cannot be renamed or deleted.
        </p>
        <Accordion
            type="multiple"
            class="bg-card w-full rounded-xl border px-4 sm:px-6"
        >
            <RolePermissionEditor
                v-for="role in roles"
                :key="role.id"
                :role="role"
                :permissions="permissions"
            />
        </Accordion>
    </div>
</template>
