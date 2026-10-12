<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { update } from '@/actions/App/Http/Controllers/SuperAdmin/RolePermissionController';
import InputError from '@/components/InputError.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import type { StaffPermission } from '@/types';
import type { PermissionOption, RolePermission } from '@/types/role-permission';
import {
    permissionCategories,
    type PermissionCategory,
} from './permission-categories';

const props = defineProps<{
    role: RolePermission;
    permissions: PermissionOption[];
}>();
const form = useForm({ grants: [...props.role.grants] });
const categories: {
    value: PermissionCategory;
    label: string;
    description: string;
    badgeClass: string;
}[] = [
    {
        value: 'read',
        label: 'Read',
        description: 'View records and retrieve information.',
        badgeClass: 'border-primary/20 bg-primary/10 text-primary',
    },
    {
        value: 'write',
        label: 'Write',
        description: 'Create, edit, and manage records.',
        badgeClass: 'bg-secondary text-secondary-foreground',
    },
    {
        value: 'destructive',
        label: 'Destructive',
        description:
            'Delete records, deactivate Properties, revoke Invitations, or end Memberships.',
        badgeClass: 'border-destructive/20 bg-destructive/10 text-destructive',
    },
];
const groups = computed(() =>
    categories
        .map((category) => {
            const permissions = props.permissions.filter(
                (permission) =>
                    permissionCategories[permission.value] === category.value,
            );
            const enabledCount = permissions.filter((permission) =>
                form.grants.includes(permission.value),
            ).length;
            return {
                ...category,
                permissions,
                enabledCount,
                allEnabled:
                    permissions.length > 0 &&
                    enabledCount === permissions.length,
            };
        })
        .filter((group) => group.permissions.length > 0),
);
watch(
    () => props.role.grants,
    (grants, previousGrants) => {
        if (
            grants.length === previousGrants.length &&
            grants.every((grant) => previousGrants.includes(grant))
        ) {
            return;
        }
        form.defaults({ grants: [...grants] });
        form.reset();
    },
);
function toggle(permission: StaffPermission, checked: boolean) {
    setPermissions([permission], checked);
}
function setPermissions(permissions: StaffPermission[], enabled: boolean) {
    if (form.processing) return;
    const values = new Set(permissions);
    form.grants = enabled
        ? [...new Set([...form.grants, ...permissions])]
        : form.grants.filter((grant) => !values.has(grant));
}
function save() {
    form.put(update.url(props.role.name), { preserveScroll: true });
}
</script>

<template>
    <AccordionItem :value="role.name">
        <div class="flex flex-wrap items-center gap-x-4">
            <div class="min-w-0 flex-1">
                <AccordionTrigger
                    class="items-center text-base [&>svg]:translate-y-0"
                >
                    <span class="space-y-1">
                        <span class="block font-medium">{{ role.label }}</span>
                        <span
                            class="text-muted-foreground block text-sm font-normal"
                        >
                            {{ form.grants.length }} of
                            {{ permissions.length }} permissions enabled
                        </span>
                    </span>
                </AccordionTrigger>
            </div>
            <div class="flex shrink-0 gap-2 pb-4 sm:pb-0">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="form.processing"
                    :aria-label="'Enable all ' + role.label + ' permissions'"
                    @click="
                        setPermissions(
                            permissions.map((permission) => permission.value),
                            true,
                        )
                    "
                    >All on</Button
                >
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="form.processing"
                    :aria-label="'Disable all ' + role.label + ' permissions'"
                    @click="
                        setPermissions(
                            permissions.map((permission) => permission.value),
                            false,
                        )
                    "
                    >All off</Button
                >
            </div>
        </div>
        <AccordionContent>
            <form @submit.prevent="save">
                <Accordion
                    type="multiple"
                    :default-value="['read']"
                    class="space-y-3"
                >
                    <AccordionItem
                        v-for="group in groups"
                        :key="group.value"
                        :value="group.value"
                        class="overflow-hidden rounded-lg border last:border-b"
                    >
                        <div class="bg-muted/30 flex items-center gap-3 px-4">
                            <div class="min-w-0 flex-1">
                                <AccordionTrigger>
                                    <span
                                        class="flex flex-wrap items-center gap-x-3 gap-y-1"
                                    >
                                        <Badge
                                            variant="outline"
                                            :class="group.badgeClass"
                                            >{{ group.label }}</Badge
                                        >
                                        <span
                                            class="text-muted-foreground text-xs font-normal"
                                            >{{ group.enabledCount }} of
                                            {{ group.permissions.length }}
                                            on</span
                                        >
                                    </span>
                                </AccordionTrigger>
                            </div>
                            <span
                                class="text-muted-foreground hidden shrink-0 text-xs sm:block"
                                >{{
                                    group.allEnabled
                                        ? 'All on'
                                        : group.enabledCount === 0
                                          ? 'All off'
                                          : 'Some on'
                                }}</span
                            >
                            <Switch
                                :model-value="group.allEnabled"
                                :disabled="form.processing"
                                :aria-label="
                                    'All ' +
                                    group.label +
                                    ' permissions for ' +
                                    role.label
                                "
                                @update:model-value="
                                    setPermissions(
                                        group.permissions.map(
                                            (permission) => permission.value,
                                        ),
                                        $event,
                                    )
                                "
                            />
                        </div>
                        <AccordionContent class="pb-0">
                            <fieldset
                                :aria-label="group.label + ' permissions'"
                                :disabled="form.processing"
                                class="divide-border/60 divide-y border-t"
                            >
                                <legend class="sr-only">
                                    {{ group.description }}
                                </legend>
                                <div
                                    v-for="permission in group.permissions"
                                    :key="permission.value"
                                    class="flex items-start justify-between gap-4 px-4 py-4"
                                >
                                    <div class="min-w-0 space-y-2">
                                        <Label
                                            :for="
                                                role.name +
                                                '-' +
                                                permission.value
                                            "
                                            class="leading-5"
                                            >{{ permission.label }}</Label
                                        >
                                        <p
                                            :id="
                                                role.name +
                                                '-' +
                                                permission.value +
                                                '-description'
                                            "
                                            class="text-muted-foreground text-sm leading-6"
                                        >
                                            {{ permission.description }}
                                        </p>
                                        <Badge
                                            variant="outline"
                                            class="text-muted-foreground font-normal"
                                            >{{ permission.feature }}</Badge
                                        >
                                    </div>
                                    <Switch
                                        :id="role.name + '-' + permission.value"
                                        :model-value="
                                            form.grants.includes(
                                                permission.value,
                                            )
                                        "
                                        :aria-describedby="
                                            role.name +
                                            '-' +
                                            permission.value +
                                            '-description'
                                        "
                                        :disabled="form.processing"
                                        class="mt-0.5"
                                        @update:model-value="
                                            toggle(permission.value, $event)
                                        "
                                    />
                                </div>
                            </fieldset>
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>
                <InputError
                    v-for="(error, field) in form.errors"
                    :key="field"
                    :message="error"
                    class="mt-4"
                />
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <Button
                        type="submit"
                        :disabled="form.processing || !form.isDirty"
                        >{{
                            form.processing
                                ? 'Saving…'
                                : 'Save ' + role.label + ' permissions'
                        }}</Button
                    >
                    <p
                        v-if="form.recentlySuccessful"
                        role="status"
                        class="text-primary text-sm"
                    >
                        Permissions saved. Changes apply on the next request.
                    </p>
                </div>
            </form>
        </AccordionContent>
    </AccordionItem>
</template>
