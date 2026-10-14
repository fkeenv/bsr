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
import EventFormFields from '@/pages/officer/events/EventFormFields.vue';
import { cancel, edit, index, publish, update } from '@/routes/officer/events';
import type { AssociationEvent } from '@/types/event';
const props = defineProps<{ event: AssociationEvent }>();
const description = ref(props.event.description);
const cancellationOpen = ref(false);
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
                                its registration history will be retained. This
                                cannot be undone.</DialogDescription
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
        <Button variant="ghost" as-child
            ><Link :href="index()">Back to Events</Link></Button
        >
    </div>
</template>
