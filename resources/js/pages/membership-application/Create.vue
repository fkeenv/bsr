<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Car,
    CircleAlert,
    CircleCheck,
    ClipboardList,
    Clock,
    FileCheck,
    Home,
    MapPin,
    NotebookPen,
    Pencil,
    Phone,
    Plus,
    Send,
    Trash2,
    Users,
    X,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import MembershipApplicationController from '@/actions/App/Http/Controllers/MembershipApplicationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
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
    property_label: string | null;
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

const isEditing = ref(false);

const isPending = computed(() => props.application?.status === 'pending');

const isLocked = computed(() => isPending.value && !isEditing.value);

const isRejected = computed(() => props.application?.status === 'rejected');

const isApproved = computed(() => props.application?.status === 'approved');

const form = useForm({
    property_id: props.application?.property_id?.toString() ?? '',
    note: props.application?.note ?? '',
    accept_terms: isPending.value,
    accept_privacy: isPending.value,
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

const propertySearchOpen = ref(false);
const propertyQuery = ref('');
const propertySelectRoot = ref<HTMLElement | null>(null);

const selectedPropertyLabel = computed(() => {
    if (form.property_id) {
        const matched = props.propertyOptions.find(
            (property) => String(property.id) === form.property_id,
        )?.label;

        if (matched) {
            return matched;
        }
    }

    return props.application?.property_label ?? null;
});

const filteredProperties = computed(() => {
    const query = propertyQuery.value.trim().toLowerCase();

    if (query === '') {
        return props.propertyOptions;
    }

    return props.propertyOptions.filter((property) =>
        property.label.toLowerCase().includes(query),
    );
});

const resetFormFromApplication = () => {
    form.property_id = props.application?.property_id?.toString() ?? '';
    form.note = props.application?.note ?? '';
    form.accept_terms = isPending.value;
    form.accept_privacy = isPending.value;
    form.household_members = props.application?.household_members?.length
        ? props.application.household_members.map((member) => ({
              name: member.name,
          }))
        : [];
    form.emergency_contacts = props.application?.emergency_contacts?.length
        ? props.application.emergency_contacts.map((contact) => ({
              name: contact.name,
              contact_number: contact.contact_number,
              relationship: contact.relationship,
          }))
        : [];
    form.vehicles = props.application?.vehicles?.length
        ? props.application.vehicles.map((vehicle) => ({
              year: vehicle.year.toString(),
              make: vehicle.make,
              model: vehicle.model,
              plate: vehicle.plate,
              sticker_number: vehicle.sticker_number,
          }))
        : [];
    form.clearErrors();
    propertySearchOpen.value = false;
    propertyQuery.value = '';
};

const startEditing = () => {
    isEditing.value = true;
    form.accept_terms = true;
    form.accept_privacy = true;
};

const cancelEditing = () => {
    resetFormFromApplication();
    isEditing.value = false;
};

const openPropertySelect = () => {
    if (isLocked.value) {
        return;
    }

    propertySearchOpen.value = true;
    propertyQuery.value = '';
};

const selectProperty = (property: PropertyOption) => {
    form.property_id = String(property.id);
    propertySearchOpen.value = false;
    propertyQuery.value = '';
};

const clearProperty = () => {
    if (isLocked.value) {
        return;
    }

    form.property_id = '';
    propertyQuery.value = '';
    propertySearchOpen.value = true;
};

const onDocumentPointerDown = (event: PointerEvent) => {
    const root = propertySelectRoot.value;

    if (
        root !== null &&
        event.target instanceof Node &&
        !root.contains(event.target)
    ) {
        propertySearchOpen.value = false;
        propertyQuery.value = '';
    }
};

onMounted(() => {
    document.addEventListener('pointerdown', onDocumentPointerDown);
});

onUnmounted(() => {
    document.removeEventListener('pointerdown', onDocumentPointerDown);
});

const submit = () => {
    if (isLocked.value) {
        return;
    }

    form.transform((data) => ({
        ...data,
        accept_terms: data.accept_terms ? '1' : '0',
        accept_privacy: data.accept_privacy ? '1' : '0',
    })).post(MembershipApplicationController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
            form.accept_terms = true;
            form.accept_privacy = true;
        },
        onFinish: () => {
            form.transform((data) => data);
        },
    });
};

const addHouseholdMember = () => {
    if (isLocked.value) {
        return;
    }

    form.household_members.push({ name: '' });
};

const removeHouseholdMember = (index: number) => {
    if (isLocked.value) {
        return;
    }

    form.household_members.splice(index, 1);
};

const addEmergencyContact = () => {
    if (isLocked.value) {
        return;
    }

    form.emergency_contacts.push({
        name: '',
        contact_number: '',
        relationship: '',
    });
};

const removeEmergencyContact = (index: number) => {
    if (isLocked.value) {
        return;
    }

    form.emergency_contacts.splice(index, 1);
};

const addVehicle = () => {
    if (isLocked.value) {
        return;
    }

    form.vehicles.push({
        year: '',
        make: '',
        model: '',
        plate: '',
        sticker_number: '',
    });
};

const removeVehicle = (index: number) => {
    if (isLocked.value) {
        return;
    }

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

    <div class="flex w-full flex-col space-y-6 p-4">
        <div class="mx-auto w-full max-w-3xl space-y-6">
            <div class="flex items-start gap-3">
                <div
                    class="bg-primary/10 text-primary mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-md"
                >
                    <ClipboardList class="size-5" />
                </div>
                <Heading
                    title="Membership Application"
                    description="Apply to be recognised on one roster Property. An Officer records owner or resident on approval."
                />
            </div>

            <Alert v-if="isPending" variant="warning" class="block">
                <div class="flex items-start gap-3">
                    <Clock
                        class="mt-1 size-5 shrink-0 text-yellow-700 dark:text-yellow-300"
                    />
                    <div class="min-w-0 space-y-2">
                        <AlertTitle
                            class="col-start-auto line-clamp-none text-lg leading-snug font-semibold tracking-tight"
                        >
                            Your application has been submitted
                        </AlertTitle>
                        <AlertDescription
                            class="col-start-auto text-sm font-normal text-inherit"
                        >
                            <p v-if="isLocked">
                                Your application has been received and is
                                awaiting review. An Officer will review your
                                application. If you need to update your details,
                                click on Edit application below.
                            </p>
                            <p v-else>
                                Your application has been received and is
                                awaiting review. You are currently editing your
                                details. Select Save changes to update your
                                submission, or Cancel to discard your edits.
                            </p>
                        </AlertDescription>
                    </div>
                </div>
            </Alert>

            <Alert v-else-if="isRejected" variant="destructive" class="block">
                <div class="flex items-start gap-3">
                    <CircleAlert
                        class="mt-1 size-5 shrink-0 text-red-700 dark:text-red-300"
                    />
                    <div class="min-w-0 space-y-2">
                        <AlertTitle
                            class="col-start-auto line-clamp-none text-lg leading-snug font-semibold tracking-tight"
                        >
                            Your application has been rejected
                        </AlertTitle>
                        <AlertDescription
                            class="col-start-auto text-sm font-normal text-inherit"
                        >
                            <p>
                                An Officer has rejected this Membership
                                Application. You may revise the details below
                                and submit your application again.
                            </p>
                        </AlertDescription>
                    </div>
                </div>
            </Alert>

            <Alert v-else-if="isApproved" variant="success" class="block">
                <div class="flex items-start gap-3">
                    <CircleCheck
                        class="mt-1 size-5 shrink-0 text-green-700 dark:text-green-300"
                    />
                    <div class="min-w-0 space-y-2">
                        <AlertTitle
                            class="col-start-auto line-clamp-none text-lg leading-snug font-semibold tracking-tight"
                        >
                            Your application has been approved
                        </AlertTitle>
                        <AlertDescription
                            class="col-start-auto space-y-2 text-sm font-normal text-inherit"
                        >
                            <p>
                                An Officer has approved your Membership
                                Application. You now hold a live Membership on
                                this Property.
                            </p>
                        </AlertDescription>
                    </div>
                </div>
            </Alert>

            <div v-if="isLocked" class="flex flex-col gap-2 sm:flex-row">
                <Button
                    type="button"
                    variant="outline"
                    class="w-full sm:w-auto"
                    @click="startEditing"
                >
                    <Pencil class="size-4" />
                    Edit application
                </Button>
            </div>

            <form class="w-full space-y-8" @submit.prevent="submit">
                <div v-if="isEditing" class="flex flex-col gap-2 sm:flex-row">
                    <Button
                        type="submit"
                        class="w-full sm:w-auto"
                        :disabled="
                            form.processing || !termsOfService || !privacyPolicy
                        "
                    >
                        <Send class="size-4" />
                        Save changes
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        class="w-full sm:w-auto"
                        :disabled="form.processing"
                        @click="cancelEditing"
                    >
                        <X class="size-4" />
                        Cancel
                    </Button>
                </div>

                <section class="space-y-3 rounded-lg border p-4">
                    <div class="flex items-center gap-2">
                        <MapPin class="text-muted-foreground size-4" />
                        <h2 class="text-base font-medium">Property</h2>
                    </div>

                    <div ref="propertySelectRoot" class="relative grid gap-2">
                        <Label for="property_search">Search roster</Label>

                        <div
                            v-if="isLocked"
                            class="border-input bg-muted/40 text-muted-foreground flex h-9 w-full items-center rounded-md border px-3 py-1 text-sm"
                        >
                            {{ selectedPropertyLabel }}
                        </div>

                        <button
                            v-else-if="
                                selectedPropertyLabel && !propertySearchOpen
                            "
                            id="property_search"
                            type="button"
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full items-center justify-between rounded-md border px-3 py-1 text-left text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                            @click="clearProperty"
                        >
                            <span>{{ selectedPropertyLabel }}</span>
                            <span class="text-muted-foreground text-xs"
                                >Change</span
                            >
                        </button>

                        <Input
                            v-else
                            id="property_search"
                            v-model="propertyQuery"
                            type="search"
                            autocomplete="off"
                            placeholder="Search by block or lot…"
                            @focus="openPropertySelect"
                        />

                        <div
                            v-if="propertySearchOpen && !isLocked"
                            class="border-input bg-popover absolute top-full z-20 mt-1 max-h-60 w-full overflow-auto rounded-md border shadow-md"
                        >
                            <button
                                v-for="property in filteredProperties"
                                :key="property.id"
                                type="button"
                                class="hover:bg-accent hover:text-accent-foreground flex w-full px-3 py-2 text-left text-sm"
                                @click="selectProperty(property)"
                            >
                                {{ property.label }}
                            </button>
                            <p
                                v-if="filteredProperties.length === 0"
                                class="text-muted-foreground px-3 py-2 text-sm"
                            >
                                No matching Properties.
                            </p>
                        </div>

                        <InputError :message="form.errors.property_id" />
                    </div>
                </section>

                <section class="space-y-3 rounded-lg border p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <Users class="text-muted-foreground size-4" />
                            <h2 class="text-base font-medium">
                                Household Members
                            </h2>
                        </div>
                        <Button
                            v-if="!isLocked"
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="addHouseholdMember"
                        >
                            <Plus class="size-4" />
                            Add
                        </Button>
                    </div>
                    <p class="text-muted-foreground text-sm">
                        Optional. Names only — listing someone does not create a
                        login.
                    </p>
                    <p
                        v-if="isLocked && form.household_members.length === 0"
                        class="text-muted-foreground text-sm"
                    >
                        None listed.
                    </p>
                    <div
                        v-for="(member, index) in form.household_members"
                        :key="index"
                        class="flex items-start gap-2"
                    >
                        <div class="grid flex-1 gap-2">
                            <div class="relative">
                                <Home
                                    class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                                />
                                <Input
                                    v-model="member.name"
                                    class="pl-9"
                                    placeholder="Name"
                                    :required="!isLocked"
                                    :readonly="isLocked"
                                />
                            </div>
                            <InputError
                                :message="
                                    form.errors[
                                        `household_members.${index}.name`
                                    ]
                                "
                            />
                        </div>
                        <Button
                            v-if="!isLocked"
                            type="button"
                            variant="destructive"
                            size="sm"
                            @click="removeHouseholdMember(index)"
                        >
                            <Trash2 class="size-4" />
                            Remove
                        </Button>
                    </div>
                </section>

                <section class="space-y-3 rounded-lg border p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <Phone class="text-muted-foreground size-4" />
                            <h2 class="text-base font-medium">
                                Emergency Contacts
                            </h2>
                        </div>
                        <Button
                            v-if="!isLocked"
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
                        v-if="isLocked && form.emergency_contacts.length === 0"
                        class="text-muted-foreground text-sm"
                    >
                        None listed.
                    </p>
                    <div
                        v-for="(contact, index) in form.emergency_contacts"
                        :key="index"
                        class="bg-muted/20 space-y-3 rounded-md border p-3"
                    >
                        <div class="grid gap-2">
                            <Label :for="`emergency_name_${index}`">Name</Label>
                            <Input
                                :id="`emergency_name_${index}`"
                                v-model="contact.name"
                                :required="!isLocked"
                                :readonly="isLocked"
                            />
                            <InputError
                                :message="
                                    form.errors[
                                        `emergency_contacts.${index}.name`
                                    ]
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
                                    :required="!isLocked"
                                    :readonly="isLocked"
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
                                    :required="!isLocked"
                                    :readonly="isLocked"
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
                            v-if="!isLocked"
                            type="button"
                            variant="destructive"
                            size="sm"
                            @click="removeEmergencyContact(index)"
                        >
                            <Trash2 class="size-4" />
                            Remove
                        </Button>
                    </div>
                </section>

                <section class="space-y-3 rounded-lg border p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <Car class="text-muted-foreground size-4" />
                            <h2 class="text-base font-medium">Vehicles</h2>
                        </div>
                        <Button
                            v-if="!isLocked"
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
                        v-if="isLocked && form.vehicles.length === 0"
                        class="text-muted-foreground text-sm"
                    >
                        None listed.
                    </p>
                    <div
                        v-for="(vehicle, index) in form.vehicles"
                        :key="index"
                        class="bg-muted/20 space-y-3 rounded-md border p-3"
                    >
                        <div class="grid gap-2 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label :for="`vehicle_year_${index}`"
                                    >Year</Label
                                >
                                <Input
                                    :id="`vehicle_year_${index}`"
                                    v-model="vehicle.year"
                                    type="number"
                                    :required="!isLocked"
                                    :readonly="isLocked"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label :for="`vehicle_make_${index}`"
                                    >Make</Label
                                >
                                <Input
                                    :id="`vehicle_make_${index}`"
                                    v-model="vehicle.make"
                                    :required="!isLocked"
                                    :readonly="isLocked"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label :for="`vehicle_model_${index}`"
                                    >Model</Label
                                >
                                <Input
                                    :id="`vehicle_model_${index}`"
                                    v-model="vehicle.model"
                                    :required="!isLocked"
                                    :readonly="isLocked"
                                />
                            </div>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label :for="`vehicle_plate_${index}`"
                                    >Plate</Label
                                >
                                <Input
                                    :id="`vehicle_plate_${index}`"
                                    v-model="vehicle.plate"
                                    :required="!isLocked"
                                    :readonly="isLocked"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label :for="`vehicle_sticker_${index}`"
                                    >Sticker number</Label
                                >
                                <Input
                                    :id="`vehicle_sticker_${index}`"
                                    v-model="vehicle.sticker_number"
                                    :required="!isLocked"
                                    :readonly="isLocked"
                                />
                            </div>
                        </div>
                        <Button
                            v-if="!isLocked"
                            type="button"
                            variant="destructive"
                            size="sm"
                            @click="removeVehicle(index)"
                        >
                            <Trash2 class="size-4" />
                            Remove
                        </Button>
                    </div>
                </section>

                <section class="space-y-3 rounded-lg border p-4">
                    <div class="flex items-center gap-2">
                        <NotebookPen class="text-muted-foreground size-4" />
                        <Label for="note">Note (optional)</Label>
                    </div>
                    <textarea
                        id="note"
                        v-model="form.note"
                        rows="4"
                        :readonly="isLocked"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring read-only:bg-muted/40 flex min-h-24 w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none"
                    />
                    <InputError :message="form.errors.note" />
                </section>

                <section class="space-y-4 rounded-lg border p-4">
                    <div class="flex items-center gap-2">
                        <FileCheck class="text-muted-foreground size-4" />
                        <h2 class="text-base font-medium">Legal acceptance</h2>
                    </div>

                    <label
                        class="flex items-start gap-3 rounded-md border p-3"
                        :class="
                            isLocked
                                ? 'bg-muted/20'
                                : 'hover:bg-muted/30 cursor-pointer'
                        "
                    >
                        <input
                            id="accept_terms"
                            v-model="form.accept_terms"
                            type="checkbox"
                            :disabled="isLocked"
                            class="border-input text-primary focus-visible:ring-ring accent-primary mt-0.5 size-4 shrink-0 rounded-[4px] border shadow-xs focus-visible:ring-[3px] focus-visible:outline-none disabled:opacity-100"
                        />
                        <span class="grid gap-1 text-sm">
                            <span>
                                I accept the
                                <Link
                                    :href="showTermsOfService()"
                                    class="underline"
                                    target="_blank"
                                    @click.stop
                                >
                                    Terms of Service
                                </Link>
                            </span>
                            <InputError :message="form.errors.accept_terms" />
                        </span>
                    </label>

                    <label
                        class="flex items-start gap-3 rounded-md border p-3"
                        :class="
                            isLocked
                                ? 'bg-muted/20'
                                : 'hover:bg-muted/30 cursor-pointer'
                        "
                    >
                        <input
                            id="accept_privacy"
                            v-model="form.accept_privacy"
                            type="checkbox"
                            :disabled="isLocked"
                            class="border-input text-primary focus-visible:ring-ring accent-primary mt-0.5 size-4 shrink-0 rounded-[4px] border shadow-xs focus-visible:ring-[3px] focus-visible:outline-none disabled:opacity-100"
                        />
                        <span class="grid gap-1 text-sm">
                            <span>
                                I accept the
                                <Link
                                    :href="showPrivacyPolicy()"
                                    class="underline"
                                    target="_blank"
                                    @click.stop
                                >
                                    Privacy Policy
                                </Link>
                            </span>
                            <InputError :message="form.errors.accept_privacy" />
                        </span>
                    </label>

                    <p
                        v-if="!isLocked && (!termsOfService || !privacyPolicy)"
                        class="text-destructive text-sm"
                    >
                        Terms of Service and Privacy Policy must be published
                        before you can apply.
                    </p>
                </section>

                <div
                    v-if="!isLocked && !isEditing"
                    class="flex flex-col gap-2 sm:flex-row"
                >
                    <Button
                        type="submit"
                        class="w-full sm:w-auto"
                        :disabled="
                            form.processing || !termsOfService || !privacyPolicy
                        "
                    >
                        <Send class="size-4" />
                        {{
                            isRejected
                                ? 'Submit again'
                                : 'Submit Membership Application'
                        }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
