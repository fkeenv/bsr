<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import MembershipApplicationController from '@/actions/App/Http/Controllers/MembershipApplicationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create as membershipApplicationCreate } from '@/routes/membership-application';
import { show as showPrivacyPolicy } from '@/routes/privacy-policy';
import { show as showTermsOfService } from '@/routes/terms-of-service';
import type { LegalDocumentVersion } from '@/types/legal-document';

type PropertyOption = {
    id: number;
    label: string;
};

type Application = {
    id: number;
    property_id: number;
    status: string;
    note: string | null;
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
    application: Application | null;
    propertyOptions: PropertyOption[];
    termsOfService: LegalDocumentVersion | null;
    privacyPolicy: LegalDocumentVersion | null;
};

const props = defineProps<Props>();

const form = useForm({
    property_id: props.application?.property_id?.toString() ?? '',
    note: props.application?.note ?? '',
    accept_terms: false,
    accept_privacy: false,
    household_members: props.application?.household_members?.length
        ? props.application.household_members.map((member) => ({
              name: member.name,
          }))
        : [],
    emergency_contacts: props.application?.emergency_contacts?.length
        ? props.application.emergency_contacts.map((contact) => ({
              name: contact.name,
              contact_number: contact.contact_number,
              relationship: contact.relationship,
          }))
        : [],
    vehicles: props.application?.vehicles?.length
        ? props.application.vehicles.map((vehicle) => ({
              year: vehicle.year.toString(),
              make: vehicle.make,
              model: vehicle.model,
              plate: vehicle.plate,
              sticker_number: vehicle.sticker_number,
          }))
        : [],
});

const statusLabel = computed(() => {
    if (!props.application) {
        return null;
    }

    return props.application.status === 'rejected'
        ? 'Previously rejected — edit and resubmit'
        : 'Pending review';
});

const submit = () => {
    form.post(MembershipApplicationController.store.url(), {
        preserveScroll: true,
    });
};

const addHouseholdMember = () => {
    form.household_members.push({ name: '' });
};

const removeHouseholdMember = (index: number) => {
    form.household_members.splice(index, 1);
};

const addEmergencyContact = () => {
    form.emergency_contacts.push({
        name: '',
        contact_number: '',
        relationship: '',
    });
};

const removeEmergencyContact = (index: number) => {
    form.emergency_contacts.splice(index, 1);
};

const addVehicle = () => {
    form.vehicles.push({
        year: '',
        make: '',
        model: '',
        plate: '',
        sticker_number: '',
    });
};

const removeVehicle = (index: number) => {
    form.vehicles.splice(index, 1);
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Membership Application',
                href: membershipApplicationCreate(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Membership Application" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Membership Application"
            description="Apply to be recognised on one roster Property. An Officer records owner or resident on approval."
        />

        <p v-if="statusLabel" class="text-muted-foreground text-sm">
            {{ statusLabel }}
        </p>

        <form class="max-w-2xl space-y-8" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="property_id">Property</Label>
                <select
                    id="property_id"
                    v-model="form.property_id"
                    required
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                >
                    <option value="" disabled>Select Property</option>
                    <option
                        v-for="property in propertyOptions"
                        :key="property.id"
                        :value="String(property.id)"
                    >
                        {{ property.label }}
                    </option>
                </select>
                <InputError :message="form.errors.property_id" />
            </div>

            <section class="space-y-3">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-base font-medium">Household Members</h2>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addHouseholdMember"
                    >
                        Add
                    </Button>
                </div>
                <p class="text-muted-foreground text-sm">
                    Optional. Names only — listing someone does not create a
                    login.
                </p>
                <div
                    v-for="(member, index) in form.household_members"
                    :key="index"
                    class="flex items-start gap-2"
                >
                    <div class="grid flex-1 gap-2">
                        <Input
                            v-model="member.name"
                            :name="`household_members[${index}][name]`"
                            placeholder="Name"
                            required
                        />
                        <InputError
                            :message="
                                form.errors[`household_members.${index}.name`]
                            "
                        />
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        @click="removeHouseholdMember(index)"
                    >
                        Remove
                    </Button>
                </div>
            </section>

            <section class="space-y-3">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-base font-medium">Emergency Contacts</h2>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addEmergencyContact"
                    >
                        Add
                    </Button>
                </div>
                <div
                    v-for="(contact, index) in form.emergency_contacts"
                    :key="index"
                    class="space-y-2 rounded-md border p-3"
                >
                    <div class="grid gap-2">
                        <Label :for="`emergency_name_${index}`">Name</Label>
                        <Input
                            :id="`emergency_name_${index}`"
                            v-model="contact.name"
                            required
                        />
                        <InputError
                            :message="
                                form.errors[`emergency_contacts.${index}.name`]
                            "
                        />
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label :for="`emergency_number_${index}`"
                                >Contact number</Label
                            >
                            <Input
                                :id="`emergency_number_${index}`"
                                v-model="contact.contact_number"
                                required
                            />
                            <InputError
                                :message="
                                    form.errors[
                                        `emergency_contacts.${index}.contact_number`
                                    ]
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`emergency_relationship_${index}`"
                                >Relationship</Label
                            >
                            <Input
                                :id="`emergency_relationship_${index}`"
                                v-model="contact.relationship"
                                required
                            />
                            <InputError
                                :message="
                                    form.errors[
                                        `emergency_contacts.${index}.relationship`
                                    ]
                                "
                            />
                        </div>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        @click="removeEmergencyContact(index)"
                    >
                        Remove
                    </Button>
                </div>
            </section>

            <section class="space-y-3">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-base font-medium">Vehicles</h2>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addVehicle"
                    >
                        Add
                    </Button>
                </div>
                <div
                    v-for="(vehicle, index) in form.vehicles"
                    :key="index"
                    class="space-y-2 rounded-md border p-3"
                >
                    <div class="grid gap-2 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label :for="`vehicle_year_${index}`">Year</Label>
                            <Input
                                :id="`vehicle_year_${index}`"
                                v-model="vehicle.year"
                                type="number"
                                required
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`vehicle_make_${index}`">Make</Label>
                            <Input
                                :id="`vehicle_make_${index}`"
                                v-model="vehicle.make"
                                required
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`vehicle_model_${index}`">Model</Label>
                            <Input
                                :id="`vehicle_model_${index}`"
                                v-model="vehicle.model"
                                required
                            />
                        </div>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label :for="`vehicle_plate_${index}`">Plate</Label>
                            <Input
                                :id="`vehicle_plate_${index}`"
                                v-model="vehicle.plate"
                                required
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`vehicle_sticker_${index}`"
                                >Sticker number</Label
                            >
                            <Input
                                :id="`vehicle_sticker_${index}`"
                                v-model="vehicle.sticker_number"
                                required
                            />
                        </div>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        @click="removeVehicle(index)"
                    >
                        Remove
                    </Button>
                </div>
            </section>

            <div class="grid gap-2">
                <Label for="note">Note (optional)</Label>
                <textarea
                    id="note"
                    v-model="form.note"
                    rows="4"
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex min-h-24 w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                />
                <InputError :message="form.errors.note" />
            </div>

            <section class="space-y-3">
                <div class="flex items-start gap-3">
                    <Checkbox
                        id="accept_terms"
                        :checked="form.accept_terms"
                        @update:checked="
                            (checked: boolean | 'indeterminate') =>
                                (form.accept_terms = checked === true)
                        "
                    />
                    <div class="grid gap-1">
                        <Label for="accept_terms">
                            I accept the
                            <Link
                                :href="showTermsOfService()"
                                class="underline"
                                target="_blank"
                            >
                                Terms of Service
                            </Link>
                        </Label>
                        <InputError :message="form.errors.accept_terms" />
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <Checkbox
                        id="accept_privacy"
                        :checked="form.accept_privacy"
                        @update:checked="
                            (checked: boolean | 'indeterminate') =>
                                (form.accept_privacy = checked === true)
                        "
                    />
                    <div class="grid gap-1">
                        <Label for="accept_privacy">
                            I accept the
                            <Link
                                :href="showPrivacyPolicy()"
                                class="underline"
                                target="_blank"
                            >
                                Privacy Policy
                            </Link>
                        </Label>
                        <InputError :message="form.errors.accept_privacy" />
                    </div>
                </div>
                <p
                    v-if="!termsOfService || !privacyPolicy"
                    class="text-destructive text-sm"
                >
                    Terms of Service and Privacy Policy must be published before
                    you can apply.
                </p>
            </section>

            <Button
                type="submit"
                :disabled="form.processing || !termsOfService || !privacyPolicy"
            >
                Submit Membership Application
            </Button>
        </form>
    </div>
</template>
