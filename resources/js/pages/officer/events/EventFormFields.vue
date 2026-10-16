<script setup lang="ts">
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { eventCentavos } from '@/lib/eventMoney';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { eventDateInput } from '@/lib/eventDates';
import type { AssociationEvent } from '@/types/event';
const props = defineProps<{
    event?: AssociationEvent;
    description: string;
    errors: Record<string, string>;
}>();
const price = ref(String((props.event?.price_centavos ?? 0) / 100));
const emit = defineEmits<{ 'update:description': [value: string] }>();
const descriptionModel = computed({
    get: () => props.description,
    set: (value: string) => emit('update:description', value),
});
const dateFields = [
    { name: 'starts_at', label: 'Starts' },
    { name: 'ends_at', label: 'Ends' },
    { name: 'registration_deadline', label: 'Registration deadline' },
] as const;
</script>
<template>
    <div class="space-y-6">
        <InputError :message="errors.event" />
        <div class="grid gap-2">
            <Label for="title">Title</Label
            ><Input
                id="title"
                name="title"
                :default-value="event?.title"
                required
                maxlength="255"
            /><InputError :message="errors.title" />
        </div>
        <div class="grid gap-2">
            <Label for="description">Description</Label
            ><input
                type="hidden"
                name="description"
                :value="descriptionModel"
            /><RichTextEditor
                id="description"
                v-model="descriptionModel"
            /><InputError :message="errors.description" />
        </div>
        <div class="grid gap-2">
            <Label for="venue">Venue</Label
            ><Input
                id="venue"
                name="venue"
                :default-value="event?.venue"
                required
                maxlength="255"
            /><InputError :message="errors.venue" />
        </div>
        <p class="text-muted-foreground text-sm">
            Enter every date and time in Manila time. Published start/end times
            are locked; rescheduling will be available separately.
        </p>
        <div class="grid gap-5 sm:grid-cols-2">
            <div
                v-for="field in dateFields"
                :key="field.name"
                class="grid gap-2"
            >
                <Label :for="field.name">{{ field.label }}</Label
                ><Input
                    :id="field.name"
                    :name="field.name"
                    type="datetime-local"
                    :default-value="eventDateInput(event?.[field.name])"
                    :readonly="
                        ['published', 'rescheduled'].includes(
                            event?.status ?? '',
                        ) && field.name !== 'registration_deadline'
                    "
                    required
                /><InputError :message="errors[field.name]" />
            </div>
        </div>
        <div class="grid gap-2">
            <Label for="audience">Audience</Label
            ><Select
                name="audience"
                :default-value="event?.audience ?? 'public'"
                ><SelectTrigger id="audience"><SelectValue /></SelectTrigger
                ><SelectContent
                    ><SelectItem value="public">Public</SelectItem
                    ><SelectItem value="members-only"
                        >Members only</SelectItem
                    ></SelectContent
                ></Select
            ><InputError :message="errors.audience" />
        </div>
        <div class="grid gap-2">
            <Label for="capacity">Attendee capacity</Label
            ><Input
                id="capacity"
                name="capacity"
                type="number"
                min="0"
                max="2147483647"
                step="1"
                :default-value="event?.capacity ?? ''"
                placeholder="Leave empty for no limit"
            /><InputError :message="errors.capacity" />
        </div>
        <div class="grid gap-2">
            <Label for="image">Event image (optional)</Label
            ><img
                v-if="event?.image_url"
                :src="event.image_url"
                alt="Current Event image"
                class="max-h-48 max-w-full rounded-lg"
            /><Input
                id="image"
                name="image"
                type="file"
                accept="image/jpeg,image/png,image/webp"
            />
            <p class="text-muted-foreground text-xs">
                JPEG, PNG, or WebP, up to 10 MB.
            </p>
            <InputError :message="errors.image" />
        </div>
        <div class="grid gap-2">
            <Label for="price">Price per attendee (PHP)</Label>
            <Input
                id="price"
                v-model="price"
                type="number"
                min="0"
                max="21474836.47"
                step="0.01"
                required
            />
            <input
                type="hidden"
                name="price_centavos"
                :value="eventCentavos(price)"
            />
            <InputError :message="errors.price_centavos" />
        </div>
        <div class="grid gap-2">
            <Label for="payment_instructions"
                >External payment instructions</Label
            >
            <Textarea
                id="payment_instructions"
                name="payment_instructions"
                :default-value="event?.payment_instructions ?? ''"
                :required="Number(price) > 0"
                maxlength="10000"
                placeholder="Where and how to pay, including account details or office hours."
            />
            <InputError :message="errors.payment_instructions" />
        </div>
        <p class="text-muted-foreground text-sm">
            Enter 0 for a free Event. Paid places are reserved for 24 hours
            while attendees submit payment details. Price and audience are
            locked after the first registration.
        </p>
        <InputError :message="errors.price_centavos" />
    </div>
</template>
