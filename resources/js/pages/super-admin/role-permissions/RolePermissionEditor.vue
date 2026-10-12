<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { update } from '@/actions/App/Http/Controllers/SuperAdmin/RolePermissionController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type { AnnouncementPermission } from '@/types';
import type { PermissionOption, RolePermission } from '@/types/role-permission';

const props = defineProps<{
    role: RolePermission;
    permissions: PermissionOption[];
}>();
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
    permission: AnnouncementPermission,
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
    <Card>
        <CardHeader>
            <CardTitle>{{ role.label }}</CardTitle>
            <CardDescription
                >Announcements · {{ form.grants.length }} of
                {{ permissions.length }} permissions enabled</CardDescription
            >
        </CardHeader>
        <form @submit.prevent="save">
            <CardContent class="space-y-4">
                <fieldset
                    :disabled="form.processing"
                    class="grid gap-3 sm:grid-cols-2"
                >
                    <legend class="sr-only">
                        {{ role.label }} Announcement permissions
                    </legend>
                    <div
                        v-for="permission in permissions"
                        :key="permission.value"
                        class="flex items-start gap-3 rounded-lg border p-4"
                    >
                        <Checkbox
                            :id="role.name + '-' + permission.value"
                            :model-value="
                                form.grants.includes(permission.value)
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
                                :for="role.name + '-' + permission.value"
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
                <InputError
                    v-for="(error, field) in form.errors"
                    :key="field"
                    :message="error"
                />
            </CardContent>
            <CardFooter class="mt-6 flex flex-wrap gap-3">
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
            </CardFooter>
        </form>
    </Card>
</template>
