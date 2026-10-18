<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import ContentImage from '@/components/ContentImage.vue';
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AssociationEvent, EventManagement } from '@/types/event';
import type { EventEditorValues } from './useEventEditor';

const props = defineProps<{
    form: InertiaForm<EventEditorValues>;
    event?: AssociationEvent;
    management?: EventManagement;
    imageRevision: number;
    disabled?: boolean;
}>();
const errors = computed(() => props.form.errors as Record<string, string>);
const locked = computed(
    () => props.management?.registration_fields_locked ?? false,
);
const published = computed(() =>
    ['published', 'rescheduled'].includes(props.event?.status ?? ''),
);
const dateFields = [
    { name: 'starts_at', label: 'Starts' },
    { name: 'ends_at', label: 'Ends' },
    { name: 'registration_deadline', label: 'Registration deadline' },
] as const;
const paid = computed(
    () =>
        Number(
            locked.value
                ? (props.event?.price_centavos ?? 0) / 100
                : props.form.price,
        ) > 0,
);
function selectImage(event: Event) {
    props.form.image = (event.target as HTMLInputElement).files?.[0] ?? null;
}
</script>

<template>
    <div class="space-y-5">
        <Card>
            <CardHeader>
                <CardTitle>Details</CardTitle>
                <CardDescription
                    >Tell people what the Event is about and where to
                    go.</CardDescription
                >
            </CardHeader>
            <CardContent class="space-y-5">
                <div
                    class="grid gap-2"
                    :data-event-error="!!errors.title"
                    tabindex="-1"
                >
                    <Label for="title">Title</Label>
                    <Input
                        id="title"
                        v-model="form.title"
                        name="title"
                        required
                        maxlength="255"
                        :aria-invalid="!!errors.title"
                        aria-describedby="title-error"
                    />
                    <InputError id="title-error" :message="errors.title" />
                </div>
                <div
                    class="grid gap-2"
                    :data-event-error="!!errors.description"
                    tabindex="-1"
                >
                    <Label for="description">Description</Label>
                    <RichTextEditor
                        id="description"
                        v-model="form.description"
                        :disabled="form.processing || !!disabled"
                        :aria-invalid="!!errors.description"
                        aria-describedby="description-error"
                    />
                    <InputError
                        id="description-error"
                        :message="errors.description"
                    />
                </div>
                <div
                    class="grid gap-2"
                    :data-event-error="!!errors.venue"
                    tabindex="-1"
                >
                    <Label for="venue">Venue</Label>
                    <Input
                        id="venue"
                        v-model="form.venue"
                        name="venue"
                        required
                        maxlength="255"
                        :aria-invalid="!!errors.venue"
                        aria-describedby="venue-error"
                    />
                    <InputError id="venue-error" :message="errors.venue" />
                </div>
                <div
                    class="grid gap-2"
                    :data-event-error="!!errors.image"
                    tabindex="-1"
                >
                    <Label for="image">Event image (optional)</Label>
                    <ContentImage
                        v-if="event?.image_url"
                        :src="event.image_url"
                        alt="Current Event image"
                        class="max-w-md rounded-lg"
                        fit="contain"
                    />
                    <Input
                        id="image"
                        :key="imageRevision"
                        name="image"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        :aria-invalid="!!errors.image"
                        aria-describedby="image-help image-error"
                        @change="selectImage"
                    />
                    <p id="image-help" class="text-muted-foreground text-sm">
                        JPEG, PNG, or WebP, up to 10 MB.
                    </p>
                    <InputError id="image-error" :message="errors.image" />
                </div>
            </CardContent>
        </Card>
        <Card>
            <CardHeader>
                <CardTitle>Schedule</CardTitle>
                <CardDescription
                    >Enter every date and time in Manila time.</CardDescription
                >
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-if="published"
                    class="bg-muted/50 rounded-lg border p-4 text-sm"
                >
                    <p>
                        Published start and end times are read-only. Use
                        Reschedule Event to change the schedule and notify
                        registrants.
                    </p>
                    <div class="mt-3"><slot name="reschedule" /></div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div
                        v-for="field in dateFields"
                        :key="field.name"
                        class="grid gap-2"
                        :data-event-error="!!errors[field.name]"
                        tabindex="-1"
                    >
                        <Label :for="field.name">{{ field.label }}</Label>
                        <Input
                            :id="field.name"
                            v-model="form[field.name]"
                            :name="field.name"
                            type="datetime-local"
                            :readonly="
                                published &&
                                field.name !== 'registration_deadline'
                            "
                            required
                            :aria-invalid="!!errors[field.name]"
                            :aria-describedby="`${field.name}-error`"
                        />
                        <InputError
                            :id="`${field.name}-error`"
                            :message="errors[field.name]"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>
        <Card>
            <CardHeader>
                <CardTitle>Attendance</CardTitle>
                <CardDescription
                    >Choose who can register and how many places are
                    available.</CardDescription
                >
            </CardHeader>
            <CardContent class="grid gap-5 sm:grid-cols-2">
                <div
                    class="grid content-start gap-2"
                    :data-event-error="!!errors.audience"
                    tabindex="-1"
                >
                    <Label for="audience">Audience</Label>
                    <Input
                        v-if="locked"
                        id="audience"
                        :model-value="event?.audience_label"
                        readonly
                        aria-describedby="audience-help audience-error"
                    />
                    <Select v-else v-model="form.audience" name="audience">
                        <SelectTrigger
                            id="audience"
                            :aria-invalid="!!errors.audience"
                            aria-describedby="audience-help audience-error"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent
                            ><SelectItem value="public">Public</SelectItem
                            ><SelectItem value="members-only"
                                >Members only</SelectItem
                            ></SelectContent
                        >
                    </Select>
                    <p id="audience-help" class="text-muted-foreground text-sm">
                        {{
                            locked
                                ? 'Audience is locked because this Event has registrations, including any cancelled registrations.'
                                : 'Audience locks after the first registration.'
                        }}
                    </p>
                    <InputError
                        id="audience-error"
                        :message="errors.audience"
                    />
                </div>
                <div
                    class="grid content-start gap-2"
                    :data-event-error="!!errors.capacity"
                    tabindex="-1"
                >
                    <Label for="capacity">Attendee capacity</Label>
                    <Input
                        id="capacity"
                        v-model="form.capacity"
                        name="capacity"
                        type="number"
                        :min="management?.reserved_places ?? 0"
                        max="2147483647"
                        step="1"
                        placeholder="No limit"
                        :aria-invalid="!!errors.capacity"
                        aria-describedby="capacity-help capacity-error"
                    />
                    <p id="capacity-help" class="text-muted-foreground text-sm">
                        Leave empty for no limit.
                        <template v-if="management"
                            >{{ management.reserved_places }} places currently
                            reserved; a limit cannot be lower than
                            this.</template
                        >
                    </p>
                    <InputError
                        id="capacity-error"
                        :message="errors.capacity"
                    />
                </div>
            </CardContent>
        </Card>
        <Card>
            <CardHeader>
                <CardTitle>Price &amp; payment</CardTitle>
                <CardDescription
                    >Use ₱0 for a free Event. Paid Events have one fixed price
                    per attendee.</CardDescription
                >
            </CardHeader>
            <CardContent class="space-y-5">
                <div
                    class="grid max-w-sm gap-2"
                    :data-event-error="!!errors.price_centavos"
                    tabindex="-1"
                >
                    <Label for="price">Price per attendee (PHP)</Label>
                    <Input
                        id="price"
                        :model-value="
                            locked
                                ? (event?.price_centavos ?? 0) / 100
                                : form.price
                        "
                        type="number"
                        min="0"
                        max="21474836.47"
                        step="0.01"
                        :readonly="locked"
                        required
                        :aria-invalid="!!errors.price_centavos"
                        aria-describedby="price-help price-error"
                        @update:model-value="form.price = $event"
                    />
                    <p id="price-help" class="text-muted-foreground text-sm">
                        {{
                            locked
                                ? 'Price is locked because this Event has registrations, including any cancelled registrations.'
                                : 'Price locks after the first registration.'
                        }}
                    </p>
                    <InputError
                        id="price-error"
                        :message="errors.price_centavos"
                    />
                </div>
                <div
                    v-if="paid || errors.payment_instructions"
                    class="grid gap-2"
                    :data-event-error="!!errors.payment_instructions"
                    tabindex="-1"
                >
                    <Label for="payment_instructions"
                        >External payment instructions</Label
                    >
                    <Textarea
                        id="payment_instructions"
                        v-model="form.payment_instructions"
                        name="payment_instructions"
                        :required="paid"
                        maxlength="10000"
                        placeholder="Where and how to pay, including account details or office hours."
                        :aria-invalid="!!errors.payment_instructions"
                        aria-describedby="payment-help payment-error"
                    />
                    <p id="payment-help" class="text-muted-foreground text-sm">
                        Paid places are reserved for 24 hours while attendees
                        submit payment details.
                    </p>
                    <InputError
                        id="payment-error"
                        :message="errors.payment_instructions"
                    />
                </div>
            </CardContent>
        </Card>
    </div>
</template>
