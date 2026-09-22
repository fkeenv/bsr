<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Car, Phone, Plus, Save, Trash2, Users } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update as updatePropertyProfile } from '@/routes/property-profile';
import type {
    PropertyEmergencyContact,
    PropertyProfile,
    PropertyProfileForm,
} from '@/types/property-profile';

const props = defineProps<{
    profile: PropertyProfile;
}>();

const form = useForm<PropertyProfileForm>({
    household_members: props.profile.household_members.map((member) => ({
        ...member,
    })),
    emergency_contacts: props.profile.emergency_contacts.map((contact) => ({
        ...contact,
    })),
    vehicles: props.profile.vehicles.map((vehicle) => ({
        ...vehicle,
        year: vehicle.year.toString(),
    })),
});

const savedLabel = computed(() => {
    if (form.recentlySuccessful) {
        return 'Saved just now';
    }

    if (props.profile.saved_at === null) {
        return 'Not saved yet';
    }

    return `Last saved ${new Intl.DateTimeFormat('en-PH', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(props.profile.saved_at))}`;
});

function submit(): void {
    form.submit(updatePropertyProfile(props.profile.property_id), {
        preserveScroll: true,
    });
}

function addHouseholdMember(): void {
    form.household_members.push({ name: '' });
}

function addEmergencyContact(): void {
    const contact: PropertyEmergencyContact = {
        name: '',
        contact_number: '',
        relationship: '',
    };

    form.emergency_contacts.push(contact);
}

function addVehicle(): void {
    form.vehicles.push({
        year: '',
        make: '',
        model: '',
        plate: '',
        sticker_number: '',
    });
}
</script>

<template>
    <div class="flex flex-col gap-6 p-4">
        <Head title="Property profile" />

        <Heading
            title="Property profile"
            :description="`${profile.property_label}. Shared by every live Membership holder on this Property.`"
        />

        <form class="flex max-w-4xl flex-col gap-6" @submit.prevent="submit">
            <section class="flex flex-col gap-4 rounded-lg border p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <Users class="text-muted-foreground size-4" />
                            <h2 class="font-medium">Household Members</h2>
                        </div>
                        <p class="text-muted-foreground text-sm">
                            Optional. Names only—listing someone does not create
                            a User Account or Membership.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addHouseholdMember"
                    >
                        <Plus class="size-4" />
                        Add
                    </Button>
                </div>

                <p
                    v-if="form.household_members.length === 0"
                    class="text-muted-foreground text-sm"
                >
                    No Household Members listed.
                </p>

                <div
                    v-for="(member, index) in form.household_members"
                    :key="index"
                    class="flex items-start gap-2"
                >
                    <div class="grid flex-1 gap-2">
                        <Label :for="`household_member_${index}`">Name</Label>
                        <Input
                            :id="`household_member_${index}`"
                            v-model="member.name"
                            required
                            maxlength="255"
                        />
                        <InputError
                            :message="
                                form.errors[`household_members.${index}.name`]
                            "
                        />
                    </div>
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        class="mt-7"
                        @click="form.household_members.splice(index, 1)"
                    >
                        <Trash2 class="size-4" />
                        Remove
                    </Button>
                </div>
            </section>

            <section class="flex flex-col gap-4 rounded-lg border p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <Phone class="text-muted-foreground size-4" />
                            <h2 class="font-medium">Emergency Contacts</h2>
                        </div>
                        <p class="text-muted-foreground text-sm">
                            Optional contacts the association may call in an
                            emergency.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addEmergencyContact"
                    >
                        <Plus class="size-4" />
                        Add
                    </Button>
                </div>

                <p
                    v-if="form.emergency_contacts.length === 0"
                    class="text-muted-foreground text-sm"
                >
                    No Emergency Contacts listed.
                </p>

                <div
                    v-for="(contact, index) in form.emergency_contacts"
                    :key="index"
                    class="bg-muted/20 grid gap-4 rounded-md border p-3"
                >
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label :for="`emergency_name_${index}`">Name</Label>
                            <Input
                                :id="`emergency_name_${index}`"
                                v-model="contact.name"
                                required
                                maxlength="255"
                            />
                            <InputError
                                :message="
                                    form.errors[
                                        `emergency_contacts.${index}.name`
                                    ]
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`emergency_number_${index}`">
                                Contact number
                            </Label>
                            <Input
                                :id="`emergency_number_${index}`"
                                v-model="contact.contact_number"
                                required
                                maxlength="50"
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
                            <Label :for="`emergency_relationship_${index}`">
                                Relationship
                            </Label>
                            <Input
                                :id="`emergency_relationship_${index}`"
                                v-model="contact.relationship"
                                required
                                maxlength="255"
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
                    <div>
                        <Button
                            type="button"
                            variant="destructive"
                            size="sm"
                            @click="form.emergency_contacts.splice(index, 1)"
                        >
                            <Trash2 class="size-4" />
                            Remove contact
                        </Button>
                    </div>
                </div>
            </section>

            <section class="flex flex-col gap-4 rounded-lg border p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <Car class="text-muted-foreground size-4" />
                            <h2 class="font-medium">Vehicles</h2>
                        </div>
                        <p class="text-muted-foreground text-sm">
                            Optional vehicles kept at this Property.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addVehicle"
                    >
                        <Plus class="size-4" />
                        Add
                    </Button>
                </div>

                <p
                    v-if="form.vehicles.length === 0"
                    class="text-muted-foreground text-sm"
                >
                    No Vehicles listed.
                </p>

                <div
                    v-for="(vehicle, index) in form.vehicles"
                    :key="index"
                    class="bg-muted/20 grid gap-4 rounded-md border p-3"
                >
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label :for="`vehicle_year_${index}`">Year</Label>
                            <Input
                                :id="`vehicle_year_${index}`"
                                v-model="vehicle.year"
                                type="number"
                                min="1900"
                                max="2100"
                                required
                            />
                            <InputError
                                :message="form.errors[`vehicles.${index}.year`]"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`vehicle_make_${index}`">Make</Label>
                            <Input
                                :id="`vehicle_make_${index}`"
                                v-model="vehicle.make"
                                required
                                maxlength="255"
                            />
                            <InputError
                                :message="form.errors[`vehicles.${index}.make`]"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`vehicle_model_${index}`">Model</Label>
                            <Input
                                :id="`vehicle_model_${index}`"
                                v-model="vehicle.model"
                                required
                                maxlength="255"
                            />
                            <InputError
                                :message="
                                    form.errors[`vehicles.${index}.model`]
                                "
                            />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label :for="`vehicle_plate_${index}`">Plate</Label>
                            <Input
                                :id="`vehicle_plate_${index}`"
                                v-model="vehicle.plate"
                                required
                                maxlength="50"
                            />
                            <InputError
                                :message="
                                    form.errors[`vehicles.${index}.plate`]
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`vehicle_sticker_${index}`">
                                Sticker number
                            </Label>
                            <Input
                                :id="`vehicle_sticker_${index}`"
                                v-model="vehicle.sticker_number"
                                required
                                maxlength="50"
                            />
                            <InputError
                                :message="
                                    form.errors[
                                        `vehicles.${index}.sticker_number`
                                    ]
                                "
                            />
                        </div>
                    </div>
                    <div>
                        <Button
                            type="button"
                            variant="destructive"
                            size="sm"
                            @click="form.vehicles.splice(index, 1)"
                        >
                            <Trash2 class="size-4" />
                            Remove vehicle
                        </Button>
                    </div>
                </div>
            </section>

            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-muted-foreground text-sm">
                    {{ savedLabel }}
                </p>
                <Button type="submit" :disabled="form.processing">
                    <Save class="size-4" />
                    {{ form.processing ? 'Saving…' : 'Save profile' }}
                </Button>
            </div>
        </form>
    </div>
</template>
