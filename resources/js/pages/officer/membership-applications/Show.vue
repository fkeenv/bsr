<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Car,
    Check,
    CircleAlert,
    CircleCheck,
    ClipboardList,
    Clock,
    Mail,
    MapPin,
    NotebookPen,
    Phone,
    User,
    Users,
    X,
} from '@lucide/vue';
import { computed, type Component } from 'vue';
import OfficerMembershipApplicationController from '@/actions/App/Http/Controllers/Officer/MembershipApplicationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertTitle } from '@/components/ui/alert';
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

const props = defineProps<Props>();

const canDecide = computed(() => props.application.status !== 'approved');

const statusVariant = computed(() => {
    if (props.application.status === 'rejected') {
        return 'destructive' as const;
    }

    if (props.application.status === 'approved') {
        return 'success' as const;
    }

    return 'warning' as const;
});

const statusTitle = computed(() => {
    if (props.application.status === 'rejected') {
        return 'This application has been rejected';
    }

    if (props.application.status === 'approved') {
        return 'This application has been approved';
    }

    return 'This application is awaiting review';
});

const statusIcon = computed((): Component => {
    if (props.application.status === 'rejected') {
        return CircleAlert;
    }

    if (props.application.status === 'approved') {
        return CircleCheck;
    }

    return Clock;
});

const statusIconClass = computed(() => {
    if (props.application.status === 'rejected') {
        return 'text-red-700 dark:text-red-300';
    }

    if (props.application.status === 'approved') {
        return 'text-green-700 dark:text-green-300';
    }

    return 'text-yellow-700 dark:text-yellow-300';
});

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

    <div class="flex w-full flex-col space-y-6 p-4">
        <div class="mx-auto flex w-full max-w-3xl flex-col space-y-6">
            <Button variant="ghost" as-child class="w-fit px-0">
                <Link :href="membershipApplicationsIndex()">
                    <ArrowLeft class="size-4" />
                    Back to list
                </Link>
            </Button>

            <section v-if="canDecide" class="space-y-4 rounded-lg border p-4">
                <h2 class="text-base font-medium">Decision</h2>
                <p class="text-muted-foreground text-sm">
                    Approve this application by assigning owner or resident, or
                    reject it so the applicant can revise and resubmit.
                </p>

                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
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
                        <Button type="submit" :disabled="processing">
                            <Check class="size-4" />
                            Approve
                        </Button>
                    </Form>

                    <Form
                        v-bind="
                            OfficerMembershipApplicationController.reject.form(
                                application.id,
                            )
                        "
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                        >
                            <X class="size-4" />
                            Reject
                        </Button>
                    </Form>
                </div>
            </section>

            <div class="flex items-start gap-3">
                <div
                    class="bg-primary/10 text-primary mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-md"
                >
                    <ClipboardList class="size-5" />
                </div>
                <Heading
                    title="Review Membership Application"
                    description="Review the applicant’s details, then approve with a role or reject the application."
                />
            </div>

            <Alert :variant="statusVariant" class="block">
                <div class="flex items-start gap-3">
                    <component
                        :is="statusIcon"
                        class="mt-1 size-5 shrink-0"
                        :class="statusIconClass"
                    />
                    <div class="min-w-0">
                        <AlertTitle
                            class="col-start-auto line-clamp-none text-lg leading-snug font-semibold tracking-tight"
                        >
                            {{ statusTitle }}
                        </AlertTitle>
                    </div>
                </div>
            </Alert>

            <section class="space-y-4 rounded-lg border p-4">
                <div class="flex items-center gap-2">
                    <User class="text-muted-foreground size-4" />
                    <h2 class="text-base font-medium">Applicant</h2>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <p class="text-muted-foreground text-xs font-medium">
                            Name
                        </p>
                        <p class="text-sm font-medium">
                            {{ application.applicant_name }}
                        </p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-muted-foreground text-xs font-medium">
                            Email
                        </p>
                        <p class="flex items-center gap-2 text-sm">
                            <Mail class="text-muted-foreground size-3.5" />
                            {{ application.applicant_email }}
                        </p>
                    </div>
                    <div class="space-y-1 sm:col-span-2">
                        <p class="text-muted-foreground text-xs font-medium">
                            Property
                        </p>
                        <p class="flex items-center gap-2 text-sm font-medium">
                            <MapPin class="text-muted-foreground size-3.5" />
                            {{ application.property_label }}
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-if="application.note"
                class="space-y-3 rounded-lg border p-4"
            >
                <div class="flex items-center gap-2">
                    <NotebookPen class="text-muted-foreground size-4" />
                    <h2 class="text-base font-medium">Note</h2>
                </div>
                <p class="text-sm leading-relaxed">{{ application.note }}</p>
            </section>

            <section class="space-y-3 rounded-lg border p-4">
                <div class="flex items-center gap-2">
                    <Users class="text-muted-foreground size-4" />
                    <h2 class="text-base font-medium">Household Members</h2>
                </div>
                <p
                    v-if="application.household_members.length === 0"
                    class="text-muted-foreground text-sm"
                >
                    None listed.
                </p>
                <ul v-else class="divide-border divide-y rounded-md border">
                    <li
                        v-for="(member, index) in application.household_members"
                        :key="index"
                        class="px-3 py-2.5 text-sm"
                    >
                        {{ member.name }}
                    </li>
                </ul>
            </section>

            <section class="space-y-3 rounded-lg border p-4">
                <div class="flex items-center gap-2">
                    <Phone class="text-muted-foreground size-4" />
                    <h2 class="text-base font-medium">Emergency Contacts</h2>
                </div>
                <p
                    v-if="application.emergency_contacts.length === 0"
                    class="text-muted-foreground text-sm"
                >
                    None listed.
                </p>
                <ul v-else class="space-y-2">
                    <li
                        v-for="(
                            contact, index
                        ) in application.emergency_contacts"
                        :key="index"
                        class="bg-muted/20 space-y-1 rounded-md border p-3"
                    >
                        <p class="text-sm font-medium">{{ contact.name }}</p>
                        <p class="text-muted-foreground text-sm">
                            {{ contact.relationship }} ·
                            {{ contact.contact_number }}
                        </p>
                    </li>
                </ul>
            </section>

            <section class="space-y-3 rounded-lg border p-4">
                <div class="flex items-center gap-2">
                    <Car class="text-muted-foreground size-4" />
                    <h2 class="text-base font-medium">Vehicles</h2>
                </div>
                <p
                    v-if="application.vehicles.length === 0"
                    class="text-muted-foreground text-sm"
                >
                    None listed.
                </p>
                <ul v-else class="space-y-2">
                    <li
                        v-for="(vehicle, index) in application.vehicles"
                        :key="index"
                        class="bg-muted/20 space-y-1 rounded-md border p-3"
                    >
                        <p class="text-sm font-medium">
                            {{ vehicle.year }} {{ vehicle.make }}
                            {{ vehicle.model }}
                        </p>
                        <p class="text-muted-foreground text-sm">
                            Plate {{ vehicle.plate }} · Sticker
                            {{ vehicle.sticker_number }}
                        </p>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
