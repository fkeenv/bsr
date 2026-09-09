<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import OfficerMembershipController from '@/actions/App/Http/Controllers/Officer/MembershipController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as membershipsIndex } from '@/routes/officer/memberships';

type Membership = {
    id: number;
    role: string;
    property_label: string | null;
    user_name: string | null;
};

type Props = {
    memberships: Membership[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
            {
                title: 'Memberships',
                href: membershipsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Memberships" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Memberships"
            description="Live Memberships across the roster. Change owner/resident role or end a Membership."
        />

        <div
            v-if="memberships.length === 0"
            class="text-muted-foreground text-sm"
        >
            No live Memberships yet.
        </div>

        <ul v-else class="divide-border divide-y border-y">
            <li
                v-for="membership in memberships"
                :key="membership.id"
                class="space-y-4 py-4"
            >
                <div>
                    <p class="font-medium">{{ membership.user_name }}</p>
                    <p class="text-muted-foreground text-sm">
                        {{ membership.property_label }} · {{ membership.role }}
                    </p>
                </div>

                <div class="flex flex-col gap-4 lg:flex-row">
                    <Form
                        v-bind="
                            OfficerMembershipController.updateRole.form(
                                membership.id,
                            )
                        "
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
                        <Button type="submit" :disabled="processing">
                            Update role
                        </Button>
                    </Form>

                    <Form
                        v-bind="
                            OfficerMembershipController.end.form(membership.id)
                        "
                        class="flex flex-wrap items-end gap-2"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label :for="`end_reason_${membership.id}`"
                                >End reason</Label
                            >
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
                            :disabled="processing"
                        >
                            End Membership
                        </Button>
                    </Form>
                </div>
            </li>
        </ul>
    </div>
</template>
