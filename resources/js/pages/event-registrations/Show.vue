<script setup lang="ts">
import { Form, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EventTicketStatus from '@/components/EventTicketStatus.vue';
import { presentEventRegistration } from '@/lib/eventPresentation';
import EventPaymentFields from '@/components/EventPaymentFields.vue';
import EventFinancialHistory from '@/components/EventFinancialHistory.vue';
import { Textarea } from '@/components/ui/textarea';
import { formatEventMoney } from '@/lib/eventMoney';
import EventScheduleNotice from '@/components/EventScheduleNotice.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { formatEventDate } from '@/lib/eventDates';
import { update } from '@/routes/event-attendees';
import {
    cancel,
    index,
    show,
    payment,
    requestCancellation,
} from '@/routes/event-registrations';
import type {
    EventRegistration,
    EventFinancial,
} from '@/types/event-registration';
const props = defineProps<{
    registration: EventRegistration;
    isOwner: boolean;
    financial: EventFinancial | null;
    submissionKey: string;
}>();
const presentation = computed(() =>
    presentEventRegistration(props.registration),
);
const needsCancellationRequest = computed(
    () =>
        props.registration.price_centavos > 0 &&
        ['awaiting-review', 'confirmed'].includes(props.registration.status),
);
const hasPendingCancellation = computed(
    () =>
        props.financial?.cancellations.some(
            (request) => request.status === 'pending',
        ) ?? false,
);
const cancellationOpen = ref(false);
const editingAttendee = ref<number | null>(null);
setLayoutProps({
    breadcrumbs: [
        { title: 'My registrations', href: index() },
        {
            title: props.registration.event.title,
            href: show(props.registration.id),
        },
    ],
});
</script>
<template>
    <div class="space-y-8 p-4 sm:p-6">
        <Head :title="registration.event.title" /><Heading
            :title="registration.event.title"
            description="Registration details and individual attendee tickets."
        />
        <EventScheduleNotice :event="registration.event" />
        <div class="space-y-3">
            <Badge :variant="presentation.variant">{{
                presentation.label
            }}</Badge>
            <p>
                {{ registration.attendee_count }} attendees ·
                {{ registration.account_name }}
            </p>
            <p class="text-muted-foreground">
                {{ formatEventDate(registration.event.starts_at) }} · Manila
                time · {{ registration.event.venue }}
            </p>
            <p
                v-if="presentation.description"
                :class="
                    presentation.cancelled
                        ? 'text-destructive'
                        : 'text-muted-foreground text-sm'
                "
            >
                {{ presentation.description }}
            </p>
        </div>
        <div v-if="registration.price_centavos > 0" class="max-w-3xl space-y-6">
            <p class="text-lg font-medium">
                {{ formatEventMoney(registration.total_centavos) }} total ·
                {{ formatEventMoney(registration.price_centavos) }} per attendee
            </p>
            <section
                v-if="
                    !presentation.cancelled &&
                    registration.status === 'pending-payment' &&
                    isOwner
                "
                class="bg-card space-y-5 rounded-xl border p-5"
            >
                <h2 class="font-serif text-2xl">Submit payment details</h2>
                <p>
                    Reserved until
                    {{ formatEventDate(registration.expires_at ?? '') }} ·
                    Manila time.
                </p>
                <p class="whitespace-pre-wrap">
                    {{ registration.payment_instructions }}
                </p>
                <Form
                    v-bind="payment.form(registration.id)"
                    class="space-y-5"
                    v-slot="{ errors, processing }"
                >
                    <EventPaymentFields :errors="errors" receipt /><InputError
                        :message="errors.payment"
                    />
                    <Button type="submit" :disabled="processing"
                        >Submit payment for review</Button
                    >
                </Form>
            </section>
            <p
                v-if="financial?.full_refund_eligible"
                class="bg-muted rounded-lg p-4"
            >
                This Event was rescheduled. You may request whole-registration
                cancellation and a full refund. Staff approval cancels your
                tickets; money returned is recorded separately.
            </p>
            <EventFinancialHistory v-if="financial" :financial="financial" />
        </div>
        <div class="grid max-w-5xl gap-5 md:grid-cols-2 xl:grid-cols-3">
            <Card v-for="ticket in registration.tickets" :key="ticket.id"
                ><CardContent class="space-y-4">
                    <h2 class="font-serif text-2xl break-words">
                        {{ ticket.name }}
                    </h2>
                    <EventTicketStatus
                        :ticket="ticket"
                        :event="registration.event"
                        :registration-status="registration.status"
                    />
                    <img
                        v-if="ticket.qr_url"
                        :src="ticket.qr_url"
                        :alt="`QR ticket for ${ticket.name}`"
                        width="256"
                        height="256"
                        class="mx-auto aspect-square max-w-full rounded-lg bg-white"
                    />
                    <Button v-if="ticket.ticket_url" variant="outline" as-child
                        ><Link :href="ticket.ticket_url"
                            >Open ticket</Link
                        ></Button
                    >
                    <Dialog
                        v-if="
                            isOwner &&
                            !ticket.checked_in_at &&
                            (ticket.valid ||
                                ([
                                    'pending-payment',
                                    'awaiting-review',
                                ].includes(registration.status) &&
                                    ['published', 'rescheduled'].includes(
                                        registration.event.status,
                                    )))
                        "
                        :open="editingAttendee === ticket.id"
                        @update:open="
                            editingAttendee = $event ? ticket.id : null
                        "
                    >
                        <DialogTrigger as-child
                            ><Button variant="ghost"
                                >Correct name</Button
                            ></DialogTrigger
                        >
                        <DialogContent
                            ><DialogHeader
                                ><DialogTitle>Correct attendee name</DialogTitle
                                ><DialogDescription
                                    >This updates the attendee name. The
                                    attendee count cannot
                                    change.</DialogDescription
                                ></DialogHeader
                            >
                            <Form
                                v-bind="update.form(ticket.id)"
                                :options="{ preserveScroll: true }"
                                @success="editingAttendee = null"
                                v-slot="{ errors, processing }"
                            >
                                <div class="grid gap-2">
                                    <Label :for="`name-${ticket.id}`"
                                        >Attendee name</Label
                                    ><Input
                                        :id="`name-${ticket.id}`"
                                        name="name"
                                        :default-value="ticket.name"
                                        required
                                        maxlength="255"
                                    /><InputError :message="errors.name" />
                                </div>
                                <DialogFooter class="mt-6"
                                    ><Button
                                        type="button"
                                        variant="secondary"
                                        @click="editingAttendee = null"
                                        >Cancel</Button
                                    ><Button
                                        type="submit"
                                        :disabled="processing"
                                        >Save name</Button
                                    ></DialogFooter
                                >
                            </Form>
                        </DialogContent>
                    </Dialog>
                </CardContent></Card
            >
        </div>
        <Dialog
            v-if="
                isOwner &&
                !registration.cancelled_at &&
                !hasPendingCancellation &&
                ['pending-payment', 'awaiting-review', 'confirmed'].includes(
                    registration.status,
                )
            "
            v-model:open="cancellationOpen"
            ><DialogTrigger as-child
                ><Button variant="destructive">{{
                    needsCancellationRequest
                        ? 'Request cancellation'
                        : 'Cancel registration'
                }}</Button></DialogTrigger
            >
            <DialogContent
                ><DialogHeader
                    ><DialogTitle>{{
                        needsCancellationRequest
                            ? 'Request whole-registration cancellation?'
                            : 'Cancel the whole registration?'
                    }}</DialogTitle
                    ><DialogDescription
                        ><template v-if="needsCancellationRequest"
                            >Staff will review your request. Places remain
                            reserved until approval. Refunds are handled
                            separately.</template
                        ><template v-else
                            >All {{ registration.attendee_count }} places will
                            be released and every ticket will become invalid.
                            This cannot be undone.</template
                        ></DialogDescription
                    ></DialogHeader
                >
                <Form
                    v-bind="
                        needsCancellationRequest
                            ? requestCancellation.form(registration.id)
                            : cancel.form(registration.id)
                    "
                    :options="{ preserveScroll: true }"
                    @success="cancellationOpen = false"
                    v-slot="{ errors, processing }"
                    ><div
                        v-if="needsCancellationRequest"
                        class="mb-5 grid gap-2"
                    >
                        <input
                            type="hidden"
                            name="submission_key"
                            :value="submissionKey"
                        />
                        <Label for="cancellation-reason"
                            >Reason for cancellation</Label
                        ><Textarea
                            id="cancellation-reason"
                            name="reason"
                            required
                            maxlength="10000"
                        /><InputError :message="errors.reason" />
                    </div>
                    <InputError :message="errors.registration" /><DialogFooter
                        ><Button
                            type="button"
                            variant="secondary"
                            @click="cancellationOpen = false"
                            >Keep registration</Button
                        ><Button
                            type="submit"
                            :disabled="processing"
                            variant="destructive"
                            >{{
                                needsCancellationRequest
                                    ? 'Send cancellation request'
                                    : 'Cancel registration'
                            }}</Button
                        ></DialogFooter
                    ></Form
                >
            </DialogContent>
        </Dialog>
        <Button variant="ghost" as-child
            ><Link :href="index()">My registrations</Link></Button
        >
    </div>
</template>
