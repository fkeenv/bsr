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
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type { StaffPermission } from '@/types';
import type { PermissionOption, RolePermission } from '@/types/role-permission';

const props = defineProps<{
    role: RolePermission;
    permissions: PermissionOption[];
}>();
const groups = computed(() => {
    const grouped = new Map<string, PermissionOption[]>();
    for (const permission of props.permissions) {
        const options = grouped.get(permission.feature) ?? [];
        options.push(permission);
        grouped.set(permission.feature, options);
    }
    return [...grouped.entries()].map(([feature, permissions]) => ({
        feature,
        permissions,
    }));
});
const form = useForm({ grants: [...props.role.grants] });
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
function toggle(
    permission: StaffPermission,
    checked: boolean | 'indeterminate',
) {
    form.grants =
        checked === true
            ? [...new Set([...form.grants, permission])]
            : form.grants.filter((grant) => grant !== permission);
}
function save() {
    form.put(update.url(props.role.name), { preserveScroll: true });
}
</script>

<template>
    <AccordionItem :value="role.name">
        <AccordionTrigger class="text-base">
            <span class="space-y-1">
                <span class="block font-medium">{{ role.label }}</span>
                <span class="text-muted-foreground block text-sm font-normal">
                    {{ form.grants.length }} of
                    {{ permissions.length }} permissions enabled
                </span>
            </span>
        </AccordionTrigger>
        <AccordionContent>
            <form @submit.prevent="save">
                <div class="space-y-4">
                    <Accordion type="multiple">
                        <AccordionItem
                            v-for="group in groups"
                            :key="group.feature"
                            :value="group.feature"
                        >
                            <AccordionTrigger>
                                <span
                                    class="flex flex-wrap items-baseline gap-x-3 gap-y-1"
                                >
                                    <span>{{ group.feature }}</span>
                                    <span
                                        class="text-muted-foreground text-xs font-normal"
                                    >
                                        {{
                                            group.permissions.filter(
                                                (permission) =>
                                                    form.grants.includes(
                                                        permission.value,
                                                    ),
                                            ).length
                                        }}
                                        of
                                        {{ group.permissions.length }} enabled
                                    </span>
                                </span>
                            </AccordionTrigger>
                            <AccordionContent>
                                <fieldset
                                    :aria-label="group.feature + ' permissions'"
                                    :disabled="form.processing"
                                    class="grid gap-3 sm:grid-cols-2"
                                >
                                    <div
                                        v-for="permission in group.permissions"
                                        :key="permission.value"
                                        class="flex items-start gap-3 rounded-lg border p-4"
                                    >
                                        <Checkbox
                                            :id="
                                                role.name +
                                                '-' +
                                                permission.value
                                            "
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
                                        <div class="space-y-1">
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
                                                class="text-muted-foreground text-sm leading-5"
                                            >
                                                {{ permission.description }}
                                            </p>
                                        </div>
                                    </div>
                                </fieldset>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                    <InputError
                        v-for="(error, field) in form.errors"
                        :key="field"
                        :message="error"
                    />
                </div>
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <Button
                        type="submit"
                        :disabled="form.processing || !form.isDirty"
                    >
                        {{
                            form.processing
                                ? 'Saving…'
                                : 'Save ' + role.label + ' permissions'
                        }}
                    </Button>
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
