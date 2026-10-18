<script setup lang="ts">
import { Form, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { eventDateInput, formatEventDate } from '@/lib/eventDates';
import EventFormFields from '@/pages/officer/events/EventFormFields.vue';
import {
    cancel,
    edit,
    index,
    publish,
    reschedule,
} from '@/routes/officer/events';
import EventSummary from './EventSummary.vue';
import { useEventEditor } from './useEventEditor';
import type {
    AssociationEvent,
    EventManagement,
    EventScheduleChange,
} from '@/types/event';
const props = defineProps<{
    event: AssociationEvent;
    scheduleChanges?: EventScheduleChange[];
    management?: EventManagement;
}>();
const { form, element, imageRevision, submit, syncSavedEvent } = useEventEditor(
    () => props.event,
    () => props.management,
);
const lifecycleBusy = ref(false);
const eventError = computed(
    () => (form.errors as Record<string, string>).event,
);
const lifecycleBlocked = computed(
    () => form.isDirty || form.processing || lifecycleBusy.value,
);
const statusLabels = {
    draft: 'Draft',
    published: 'Published',
    rescheduled: 'Rescheduled',
    cancelled: 'Cancelled',
};
const cancellationOpen = ref(false);
const rescheduleOpen = ref(false);
const scheduleFields = [
    { name: 'starts_at', label: 'Starts' },
    { name: 'ends_at', label: 'Ends' },
    { name: 'registration_deadline', label: 'Registration deadline' },
] as const;
async function rescheduled() {
    rescheduleOpen.value = false;
    await syncSavedEvent();
}
setLayoutProps({
    breadcrumbs: [
        { title: 'Events', href: index() },
        { title: props.event.title, href: edit(props.event.id) },
    ],
});
</script>
<template>
    <div class="space-y-6 p-4 sm:p-6">
        <Head :title="event.title" />
        <Dialog v-model:open="rescheduleOpen">
            <div
                class="flex max-w-3xl flex-wrap items-start justify-between gap-4"
            >
                <Heading
                    :title="event.title"
                    description="Manage the Event details and publication."
                />
                <div class="flex items-center gap-3">
                    <Badge variant="outline">{{
                        statusLabels[event.status]
                    }}</Badge>
                    <Form
                        v-if="event.status === 'draft'"
                        v-bind="publish.form(event.id)"
                        :options="{ preserveScroll: true }"
                        @before="() => !lifecycleBlocked"
                        @start="lifecycleBusy = true"
                        @finish="lifecycleBusy = false"
                        v-slot="{ errors, processing }"
                    >
                        <InputError :message="errors.event" />
                        <Button
                            type="submit"
                            :disabled="lifecycleBlocked || processing"
                            :aria-describedby="
                                form.isDirty ? 'event-unsaved' : undefined
                            "
                            >Publish Event</Button
                        >
                    </Form>
                </div>
            </div>
            <template v-if="event.status !== 'cancelled'">
                <p
                    v-if="form.isDirty"
                    id="event-unsaved"
                    role="status"
                    class="bg-muted/50 max-w-3xl rounded-lg border px-4 py-3 text-sm"
                >
                    Save your changes before publishing or rescheduling.
                </p>
                <form
                    ref="element"
                    class="max-w-3xl space-y-5"
                    @submit.prevent="!lifecycleBusy && submit()"
                >
                    <Alert
                        v-if="form.hasErrors"
                        variant="destructive"
                        role="alert"
                        data-event-error="true"
                        tabindex="-1"
                    >
                        <AlertTitle>Check the Event details</AlertTitle>
                        <AlertDescription>{{
                            eventError ||
                            'Review the errors below. Your changes have been kept.'
                        }}</AlertDescription>
                    </Alert>
                    <fieldset
                        :disabled="form.processing || lifecycleBusy"
                        class="min-w-0 space-y-5"
                    >
                        <EventFormFields
                            :form="form"
                            :event="event"
                            :management="management"
                            :disabled="lifecycleBusy"
                            :image-revision="imageRevision"
                        >
                            <template #reschedule>
                                <DialogTrigger as-child
                                    ><Button
                                        type="button"
                                        variant="outline"
                                        :disabled="lifecycleBlocked"
                                        :aria-describedby="
                                            form.isDirty
                                                ? 'event-unsaved'
                                                : undefined
                                        "
                                        >Reschedule Event</Button
                                    ></DialogTrigger
                                >
                            </template>
                        </EventFormFields>
                        <Button
                            type="submit"
                            :disabled="form.processing || lifecycleBusy"
                            >{{
                                form.processing ? 'Saving…' : 'Save changes'
                            }}</Button
                        >
                    </fieldset>
                </form>
                <DialogContent
                    v-if="['published', 'rescheduled'].includes(event.status)"
                >
                    <DialogHeader>
                        <DialogTitle>Reschedule Event</DialogTitle>
                        <DialogDescription
                            >Enter the new schedule in Manila time. Existing
                            registrants will be notified. Tickets and reserved
                            places carry over; unpaid reservations keep their
                            original 24-hour expiry.</DialogDescription
                        >
                    </DialogHeader>
                    <Form
                        v-bind="reschedule.form(event.id)"
                        :options="{ preserveScroll: true }"
                        @before="() => !lifecycleBlocked"
                        @start="lifecycleBusy = true"
                        @finish="lifecycleBusy = false"
                        @success="rescheduled"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div
                            v-for="field in scheduleFields"
                            :key="field.name"
                            class="grid gap-2"
                        >
                            <Label :for="`reschedule-${field.name}`"
                                >{{ field.label }} · Manila time</Label
                            >
                            <Input
                                :id="`reschedule-${field.name}`"
                                :name="field.name"
                                type="datetime-local"
                                :default-value="
                                    eventDateInput(event[field.name])
                                "
                                required
                            />
                            <InputError :message="errors[field.name]" />
                        </div>
                        <InputError :message="errors.event" />
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="secondary"
                                @click="rescheduleOpen = false"
                                >Keep schedule</Button
                            >
                            <Button
                                type="submit"
                                :disabled="
                                    processing ||
                                    form.processing ||
                                    form.isDirty
                                "
                                >Save new schedule</Button
                            >
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </template>
            <div v-else class="max-w-3xl space-y-5">
                <Alert
                    ><AlertTitle>Event cancelled</AlertTitle
                    ><AlertDescription
                        >This Event was cancelled. Its history is retained. Its
                        details are read-only.</AlertDescription
                    ></Alert
                >
                <EventSummary :event="event" />
            </div>
        </Dialog>
        <Accordion
            v-if="scheduleChanges?.length"
            type="single"
            collapsible
            class="max-w-3xl rounded-xl border px-5"
        >
            <AccordionItem value="schedule-history">
                <AccordionTrigger>Schedule history</AccordionTrigger>
                <AccordionContent class="space-y-5">
                    <article
                        v-for="change in scheduleChanges"
                        :key="change.id"
                        class="space-y-3 border-t pt-4 first:border-0 first:pt-0"
                    >
                        <p class="text-muted-foreground text-sm">
                            {{ formatEventDate(change.changed_at) }} · Staff
                            account #{{ change.changed_by_user_id }}
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <h3 class="font-medium">Previous schedule</h3>
                                <p>
                                    {{ formatEventDate(change.old_starts_at) }}
                                    to {{ formatEventDate(change.old_ends_at) }}
                                </p>
                                <p class="text-muted-foreground text-sm">
                                    Registration deadline:
                                    {{
                                        formatEventDate(
                                            change.old_registration_deadline,
                                        )
                                    }}
                                </p>
                            </div>
                            <div>
                                <h3 class="font-medium">New schedule</h3>
                                <p>
                                    {{ formatEventDate(change.new_starts_at) }}
                                    to {{ formatEventDate(change.new_ends_at) }}
                                </p>
                                <p class="text-muted-foreground text-sm">
                                    Registration deadline:
                                    {{
                                        formatEventDate(
                                            change.new_registration_deadline,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                        <p class="text-muted-foreground text-sm">
                            All times in Manila time.
                        </p>
                    </article>
                </AccordionContent>
            </AccordionItem>
        </Accordion>
        <Card v-if="event.status !== 'cancelled'" class="max-w-3xl">
            <CardHeader
                ><CardTitle>Cancel Event</CardTitle
                ><CardDescription
                    >Close registration and invalidate tickets while retaining
                    the Event history.</CardDescription
                ></CardHeader
            >
            <CardContent>
                <Dialog v-model:open="cancellationOpen">
                    <DialogTrigger as-child
                        ><Button
                            variant="destructive"
                            :disabled="form.processing || lifecycleBusy"
                            >Cancel Event</Button
                        ></DialogTrigger
                    >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Cancel this Event?</DialogTitle>
                            <DialogDescription
                                >It will leave the published list. The Event and
                                its registration history will be retained. All
                                tickets become invalid and reservations close.
                                Confirmed payments require refund resolution;
                                cancellation does not send money back.
                                Registrants will be notified. This cannot be
                                undone.</DialogDescription
                            >
                        </DialogHeader>
                        <p
                            v-if="form.isDirty"
                            class="bg-muted rounded-lg p-3 text-sm"
                        >
                            Unsaved edits will be discarded.
                        </p>
                        <Form
                            v-bind="cancel.form(event.id)"
                            :options="{ preserveScroll: true }"
                            @before="() => !form.processing && !lifecycleBusy"
                            @start="lifecycleBusy = true"
                            @finish="lifecycleBusy = false"
                            @success="cancellationOpen = false"
                            v-slot="{ errors, processing }"
                        >
                            <InputError :message="errors.event" />
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    @click="cancellationOpen = false"
                                    >Keep Event</Button
                                >
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    :disabled="processing || form.processing"
                                    >Cancel Event</Button
                                >
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </CardContent>
        </Card>
        <Button variant="ghost" as-child
            ><Link :href="index()">Back to Events</Link></Button
        >
    </div>
</template>
