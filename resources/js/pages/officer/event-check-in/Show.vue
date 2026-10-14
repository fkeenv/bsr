<script setup lang="ts">
import { Form, Head, Link, setLayoutProps, useHttp } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import EventQrScanner from '@/components/EventQrScanner.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatEventDate } from '@/lib/eventDates';
import { index, show, store } from '@/routes/officer/event-check-in';
import type { AssociationEvent } from '@/types/event';
import type {
    EventAttendeeLookupPage,
    EventCheckInResult,
} from '@/types/event-check-in';
const props = defineProps<{
    event: AssociationEvent;
    attendees: EventAttendeeLookupPage;
    search: string;
}>();
const rows = ref(props.attendees.data);
const scanner = ref<{ stop: () => void } | null>(null);
const result = ref<EventCheckInResult | null>(null);
const failure = ref('');
const admission = useHttp<{ ticket: string }, EventCheckInResult>({
    ticket: '',
});
watch(
    () => props.attendees,
    (attendees) => {
        rows.value = attendees.data;
    },
);
setLayoutProps({
    breadcrumbs: [
        { title: 'Event check-in', href: index() },
        { title: props.event.title, href: show(props.event.id) },
    ],
});
async function admit(ticket: string) {
    if (admission.processing) return;
    scanner.value?.stop();
    admission.ticket = ticket;
    admission.clearErrors();
    result.value = null;
    failure.value = '';
    try {
        const checkedIn = await admission.submit(store(props.event.id), {
            onHttpException: () => false,
            onNetworkError: () => false,
        });
        result.value = checkedIn;
        rows.value = rows.value.map((row) =>
            row.ticket === checkedIn.ticket
                ? {
                      ...row,
                      checked_in_at: checkedIn.checked_in_at,
                      checked_in_by_user_id: checkedIn.checked_in_by_user_id,
                  }
                : row,
        );
    } catch {
        failure.value =
            admission.errors.ticket ??
            'Check-in could not be confirmed. Check your connection and access, then try again. You can safely retry: a ticket can only be admitted once.';
    }
}
onBeforeUnmount(() => admission.cancel());
</script>
<template>
    <div class="space-y-6 p-4 sm:p-6">
        <Head :title="`Check-in · ${event.title}`" /><Heading
            :title="event.title"
            description="Event admission · Only confirmed, active tickets can be checked in."
        />
        <p class="text-muted-foreground text-sm">
            {{ formatEventDate(event.starts_at) }} · Manila time ·
            {{ event.venue }}
        </p>
        <Alert
            v-if="result"
            role="status"
            aria-live="polite"
            class="border-green-300 bg-green-50 text-green-950 dark:border-green-800 dark:bg-green-950 dark:text-green-100"
        >
            <AlertTitle>{{
                result.outcome === 'already-checked-in'
                    ? 'Already checked in'
                    : 'Checked in successfully'
            }}</AlertTitle>
            <AlertDescription class="text-current"
                >{{ result.name }} ·
                {{ formatEventDate(result.checked_in_at) }} Manila time · Staff
                account #{{ result.checked_in_by_user_id
                }}<span v-if="result.outcome === 'already-checked-in'"
                    >. No second admission was recorded.</span
                ></AlertDescription
            >
        </Alert>
        <Alert v-if="failure" variant="destructive" role="alert"
            ><AlertTitle>Admission not confirmed</AlertTitle
            ><AlertDescription>{{ failure }}</AlertDescription></Alert
        >
        <p
            v-if="admission.processing"
            role="status"
            class="text-muted-foreground"
        >
            Checking the ticket with the server…
        </p>
        <EventQrScanner
            ref="scanner"
            :disabled="admission.processing"
            @scan="admit"
        />
        <section class="space-y-5" aria-labelledby="lookup-heading">
            <h2 id="lookup-heading" class="font-serif text-2xl">
                Find an attendee
            </h2>
            <p class="text-muted-foreground text-sm">
                No camera needed. Search an attendee or the registering
                account's name. Check the Event and attendee name before
                recording admission.
            </p>
            <Form
                v-bind="show.form(event.id)"
                :options="{ preserveScroll: true }"
                class="space-y-2"
                v-slot="{ errors, processing }"
            >
                <Label for="attendee-search">Attendee or account name</Label>
                <div class="flex max-w-xl gap-2">
                    <Input
                        id="attendee-search"
                        name="search"
                        :default-value="search"
                        maxlength="255"
                        placeholder="Search by name"
                    /><Button type="submit" :disabled="processing"
                        >Search</Button
                    ><Button v-if="search" variant="ghost" as-child
                        ><Link :href="show(event.id)">Clear</Link></Button
                    >
                </div>
                <InputError :message="errors.search" />
            </Form>
            <p class="text-muted-foreground text-sm">
                {{ attendees.total }} attendees found
            </p>
            <div
                v-if="rows.length"
                class="bg-card divide-border divide-y rounded-xl border"
            >
                <article
                    v-for="attendee in rows"
                    :key="attendee.id"
                    class="flex flex-wrap items-center justify-between gap-4 p-4"
                >
                    <div class="min-w-0 space-y-2">
                        <h3 class="font-medium break-words">
                            {{ attendee.name }}
                        </h3>
                        <p class="text-muted-foreground text-sm break-words">
                            Registered by {{ attendee.account_name }}
                        </p>
                        <Badge variant="outline">{{
                            attendee.status_label
                        }}</Badge>
                        <p
                            v-if="attendee.checked_in_at"
                            class="text-muted-foreground text-sm"
                        >
                            Checked in
                            {{ formatEventDate(attendee.checked_in_at) }} Manila
                            time · Staff account #{{
                                attendee.checked_in_by_user_id
                            }}
                        </p>
                        <p
                            v-else-if="!attendee.valid"
                            class="text-muted-foreground text-sm"
                        >
                            Ticket is not valid for admission.
                        </p>
                    </div>
                    <Button
                        :disabled="
                            admission.processing ||
                            !attendee.valid ||
                            !attendee.ticket ||
                            !!attendee.checked_in_at
                        "
                        @click="attendee.ticket && admit(attendee.ticket)"
                        >{{
                            attendee.checked_in_at
                                ? 'Already checked in'
                                : 'Check in attendee'
                        }}</Button
                    >
                </article>
            </div>
            <p v-else class="text-muted-foreground rounded-xl border p-6">
                No attendees match this search. Try another name.
            </p>
            <div v-if="attendees.last_page > 1" class="flex items-center gap-3">
                <Button
                    v-if="attendees.current_page > 1"
                    variant="outline"
                    as-child
                    ><Link
                        :href="
                            show(event.id, {
                                query: {
                                    search,
                                    page: attendees.current_page - 1,
                                },
                            })
                        "
                        >Previous</Link
                    ></Button
                >
                <span class="text-muted-foreground text-sm"
                    >Page {{ attendees.current_page }} of
                    {{ attendees.last_page }}</span
                >
                <Button
                    v-if="attendees.current_page < attendees.last_page"
                    variant="outline"
                    as-child
                    ><Link
                        :href="
                            show(event.id, {
                                query: {
                                    search,
                                    page: attendees.current_page + 1,
                                },
                            })
                        "
                        >Next</Link
                    ></Button
                >
            </div>
        </section>
        <Button variant="ghost" as-child
            ><Link :href="index()">Choose another Event</Link></Button
        >
    </div>
</template>
