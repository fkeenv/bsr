<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import OfficerMembershipController from '@/actions/App/Http/Controllers/Officer/MembershipController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Membership } from '@/types/membership';

defineProps<{
    membership: Membership;
}>();
</script>

<template>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-end">
        <Form
            v-bind="OfficerMembershipController.updateRole.form(membership.id)"
            class="flex flex-wrap items-end gap-2"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label :for="`role_${membership.id}`">Role</Label>
                <select
                    :id="`role_${membership.id}`"
                    name="role"
                    required
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                >
                    <option
                        value="owner"
                        :selected="membership.role === 'owner'"
                    >
                        Owner
                    </option>
                    <option
                        value="resident"
                        :selected="membership.role === 'resident'"
                    >
                        Resident
                    </option>
                </select>
                <InputError :message="errors.role" />
            </div>
            <Button type="submit" size="sm" :disabled="processing">
                Update role
            </Button>
        </Form>

        <Form
            v-bind="OfficerMembershipController.end.form(membership.id)"
            class="flex flex-wrap items-end gap-2"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label :for="`end_reason_${membership.id}`">End reason</Label>
                <Input
                    :id="`end_reason_${membership.id}`"
                    name="end_reason"
                    required
                />
                <InputError :message="errors.end_reason" />
            </div>
            <Button
                type="submit"
                variant="outline"
                size="sm"
                :disabled="processing"
            >
                End Membership
            </Button>
        </Form>
    </div>
</template>
