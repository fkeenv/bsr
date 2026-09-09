<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import OfficerMembershipApplicationController from '@/actions/App/Http/Controllers/Officer/MembershipApplicationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as membershipApplicationsIndex } from '@/routes/officer/membership-applications';

type Application = {
    id: number;
    status: string;
    note: string | null;
    property_label: string | null;
    applicant_name: string | null;
    applicant_email: string | null;
    household_members: { name: string }[];
    emergency_contacts: {
        name: string;
        contact_number: string;
        relationship: string;
    }[];
    vehicles: {
        year: number;
        make: string;
        model: string;
        plate: string;
        sticker_number: string;
    }[];
};

type Props = {
    application: Application;
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
                title: 'Membership Applications',
                href: membershipApplicationsIndex(),
            },
            {
                title: 'Review',
            },
        ],
    },
});
</script>

<template>
    <Head title="Review Membership Application" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Review Membership Application"
            :description="`${application.applicant_name} · ${application.property_label}`"
        />

        <dl class="grid max-w-2xl gap-3 text-sm">
            <div>
                <dt class="text-muted-foreground">Applicant email</dt>
                <dd>{{ application.applicant_email }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Status</dt>
                <dd>{{ application.status }}</dd>
            </div>
            <div v-if="application.note">
                <dt class="text-muted-foreground">Note</dt>
                <dd>{{ application.note }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Household Members</dt>
                <dd>
                    <span v-if="application.household_members.length === 0"
                        >None</span
                    >
                    <ul v-else class="list-inside list-disc">
                        <li
                            v-for="(
                                member, index
                            ) in application.household_members"
                            :key="index"
                        >
                            {{ member.name }}
                        </li>
                    </ul>
                </dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Emergency Contacts</dt>
                <dd>
                    <span v-if="application.emergency_contacts.length === 0"
                        >None</span
                    >
                    <ul v-else class="list-inside list-disc">
                        <li
                            v-for="(
                                contact, index
                            ) in application.emergency_contacts"
                            :key="index"
                        >
                            {{ contact.name }} · {{ contact.contact_number }} ·
                            {{ contact.relationship }}
                        </li>
                    </ul>
                </dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Vehicles</dt>
                <dd>
                    <span v-if="application.vehicles.length === 0">None</span>
                    <ul v-else class="list-inside list-disc">
                        <li
                            v-for="(vehicle, index) in application.vehicles"
                            :key="index"
                        >
                            {{ vehicle.year }} {{ vehicle.make }}
                            {{ vehicle.model }} · {{ vehicle.plate }} ·
                            {{ vehicle.sticker_number }}
                        </li>
                    </ul>
                </dd>
            </div>
        </dl>

        <div
            v-if="application.status !== 'approved'"
            class="flex max-w-2xl flex-col gap-4 sm:flex-row"
        >
            <Form
                v-bind="
                    OfficerMembershipApplicationController.approve.form(
                        application.id,
                    )
                "
                class="flex flex-1 flex-col gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="role">Role on approval</Label>
                    <select
                        id="role"
                        name="role"
                        required
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                    >
                        <option value="owner">Owner</option>
                        <option value="resident">Resident</option>
                    </select>
                    <InputError :message="errors.role" />
                    <InputError :message="errors.application" />
                </div>
                <Button type="submit" :disabled="processing">Approve</Button>
            </Form>

            <Form
                v-bind="
                    OfficerMembershipApplicationController.reject.form(
                        application.id,
                    )
                "
                class="flex items-end"
                v-slot="{ processing }"
            >
                <Button type="submit" variant="outline" :disabled="processing">
                    Reject
                </Button>
            </Form>
        </div>

        <Button variant="ghost" as-child>
            <Link :href="membershipApplicationsIndex()">Back to list</Link>
        </Button>
    </div>
</template>
