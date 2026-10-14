<script setup lang="ts">
import { Form, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { cancel, index, show } from '@/routes/event-registrations';
import type { EventRegistration } from '@/types/event-registration';
const props = defineProps<{
    registration: EventRegistration;
    isOwner: boolean;
}>();
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
        <div class="space-y-3">
            <Badge variant="outline">{{
                registration.cancelled_at
                    ? 'Cancelled'
                    : registration.event.status === 'cancelled'
                      ? 'Event cancelled'
                      : 'Confirmed'
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
                v-if="
                    registration.cancelled_at ||
                    registration.event.status === 'cancelled'
                "
                class="text-destructive"
            >
                These tickets are invalid.
            </p>
            <p v-else class="text-muted-foreground text-sm">
                Each attendee has an individual QR ticket. You can correct a
                name before check-in. Additional attendees need a new
                registration.
            </p>
        </div>
        <div class="grid max-w-5xl gap-5 md:grid-cols-2 xl:grid-cols-3">
            <Card v-for="ticket in registration.tickets" :key="ticket.id"
                ><CardContent class="space-y-4">
                    <h2 class="font-serif text-2xl break-words">
                        {{ ticket.name }}
                    </h2>
                    <Badge variant="outline">{{
                        ticket.checked_in_at
                            ? 'Checked in'
                            : ticket.valid
                              ? 'Ready for admission'
                              : 'Invalid ticket'
                    }}</Badge>
                    <img
                        v-if="ticket.qr_url"
                        :src="ticket.qr_url"
                        :alt="`QR ticket for ${ticket.name}`"
                        width="256"
                        height="256"
                        class="mx-auto aspect-square max-w-full rounded-lg bg-white"
                    />
                    <Button variant="outline" as-child
                        ><Link :href="ticket.ticket_url"
                            >Open ticket</Link
                        ></Button
                    >
                    <Dialog
                        v-if="isOwner && ticket.valid && !ticket.checked_in_at"
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
                                    >This updates the existing ticket. The
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
            v-if="isOwner && !registration.cancelled_at"
            v-model:open="cancellationOpen"
            ><DialogTrigger as-child
                ><Button variant="destructive"
                    >Cancel registration</Button
                ></DialogTrigger
            >
            <DialogContent
                ><DialogHeader
                    ><DialogTitle>Cancel the whole registration?</DialogTitle
                    ><DialogDescription
                        >All {{ registration.attendee_count }} places will be
                        released and every ticket will become invalid. This
                        cannot be undone.</DialogDescription
                    ></DialogHeader
                >
                <Form
                    v-bind="cancel.form(registration.id)"
                    :options="{ preserveScroll: true }"
                    @success="cancellationOpen = false"
                    v-slot="{ errors, processing }"
                    ><InputError :message="errors.registration" /><DialogFooter
                        ><Button
                            type="button"
                            variant="secondary"
                            @click="cancellationOpen = false"
                            >Keep registration</Button
                        ><Button
                            type="submit"
                            :disabled="processing"
                            variant="destructive"
                            >Cancel registration</Button
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
