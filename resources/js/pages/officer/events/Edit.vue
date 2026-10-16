<script setup lang="ts">
import { Form, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
    update,
    reschedule,
} from '@/routes/officer/events';
import type { AssociationEvent, EventScheduleChange } from '@/types/event';
const props = defineProps<{
    event: AssociationEvent;
    scheduleChanges?: EventScheduleChange[];
}>();
const description = ref(props.event.description);
const cancellationOpen = ref(false);
const rescheduleOpen = ref(false);
const scheduleFields = [
    { name: 'starts_at', label: 'Starts' },
    { name: 'ends_at', label: 'Ends' },
    { name: 'registration_deadline', label: 'Registration deadline' },
] as const;
setLayoutProps({
    breadcrumbs: [
        { title: 'Events', href: index() },
        { title: props.event.title, href: edit(props.event.id) },
    ],
});
</script>
<template>
    <div class="space-y-6 p-4 sm:p-6">
        <Head :title="event.title" /><Heading
            :title="event.title"
            description="Manage the Event details and publication."
        />
        <Badge variant="outline">{{ event.status }}</Badge>
        <template v-if="event.status !== 'cancelled'">
            <Form
                v-bind="update.form(event.id)"
                :key="event.starts_at + event.ends_at"
                class="max-w-3xl space-y-6"
                v-slot="{ errors, processing }"
                :options="{ preserveScroll: true }"
            >
                <EventFormFields
                    v-model:description="description"
                    :event="event"
                    :errors="errors"
                />
                <Button type="submit" :disabled="processing"
                    >Save changes</Button
                >
            </Form>
            <div class="flex flex-wrap items-center gap-3 border-t pt-6">
                <Form
                    v-if="event.status === 'draft'"
                    v-bind="publish.form(event.id)"
                    v-slot="{ errors, processing }"
                    ><InputError :message="errors.event" /><Button
                        type="submit"
                        :disabled="processing"
                        >Publish Event</Button
                    ></Form
                >
                <Dialog
                    v-if="['published', 'rescheduled'].includes(event.status)"
                    v-model:open="rescheduleOpen"
                >
                    <DialogTrigger as-child
                        ><Button variant="outline"
                            >Reschedule Event</Button
                        ></DialogTrigger
                    >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Reschedule Event</DialogTitle>
                            <DialogDescription
                                >Enter the new schedule in Manila time. Existing
                                registrants will be notified. Tickets and
                                reserved places carry over; unpaid reservations
                                keep their original 24-hour
                                expiry.</DialogDescription
                            >
                        </DialogHeader>
                        <Form
                            v-bind="reschedule.form(event.id)"
                            :options="{ preserveScroll: true }"
                            @success="rescheduleOpen = false"
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
                            <DialogFooter
                                ><Button
                                    type="button"
                                    variant="secondary"
                                    @click="rescheduleOpen = false"
                                    >Keep schedule</Button
                                ><Button type="submit" :disabled="processing"
                                    >Save new schedule</Button
                                ></DialogFooter
                            >
                        </Form>
                    </DialogContent>
                </Dialog>
                <Dialog v-model:open="cancellationOpen"
                    ><DialogTrigger as-child
                        ><Button variant="destructive"
                            >Cancel Event</Button
                        ></DialogTrigger
                    ><DialogContent
                        ><DialogHeader
                            ><DialogTitle>Cancel this Event?</DialogTitle
                            ><DialogDescription
                                >It will leave the published list. The Event and
                                its registration history will be retained. All
                                tickets become invalid and reservations close.
                                Confirmed payments require refund resolution;
                                cancellation does not send money back.
                                Registrants will be notified. This cannot be
                                undone.</DialogDescription
                            ></DialogHeader
                        ><Form
                            v-bind="cancel.form(event.id)"
                            @success="cancellationOpen = false"
                            v-slot="{ errors, processing }"
                            ><InputError :message="errors.event" /><DialogFooter
                                ><Button
                                    type="button"
                                    variant="secondary"
                                    @click="cancellationOpen = false"
                                    >Keep Event</Button
                                ><Button
                                    type="submit"
                                    variant="destructive"
                                    :disabled="processing"
                                    >Cancel Event</Button
                                ></DialogFooter
                            ></Form
                        ></DialogContent
                    ></Dialog
                >
            </div>
        </template>
        <p v-else class="text-muted-foreground">
            This Event was cancelled. Its history is retained.
        </p>
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
        <Button variant="ghost" as-child
            ><Link :href="index()">Back to Events</Link></Button
        >
    </div>
</template>
