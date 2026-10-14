<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import { store as registerForEvent } from '@/routes/events/registrations';
import RichTextContent from '@/components/RichTextContent.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatEventMoney } from '@/lib/eventMoney';
import { formatEventDate } from '@/lib/eventDates';
import { index } from '@/routes/events';
import type { AssociationEvent } from '@/types/event';
defineOptions({ layout: [] });
defineProps<{
    event: AssociationEvent;
    registrationAvailability: string | null;
    submissionKey: string | null;
}>();
const page = usePage();
const attendees = ref([page.props.auth.user?.name ?? '']);
</script>
<template>
    <div class="bg-background text-foreground min-h-screen">
        <Head :title="event.title" />
        <main class="mx-auto max-w-3xl space-y-8 px-6 py-12 sm:px-10">
            <Button variant="outline" size="sm" as-child
                ><Link :href="index()">Back to Events</Link></Button
            >
            <article class="space-y-8">
                <header class="space-y-5">
                    <div class="flex gap-2">
                        <Badge>{{
                            event.price_centavos > 0
                                ? formatEventMoney(event.price_centavos) +
                                  ' per attendee'
                                : 'Free Event'
                        }}</Badge
                        ><Badge variant="outline">{{
                            event.audience_label
                        }}</Badge>
                    </div>
                    <h1
                        class="font-serif text-4xl leading-tight break-words sm:text-5xl"
                    >
                        {{ event.title }}
                    </h1>
                    <img
                        v-if="event.image_url"
                        :src="event.image_url"
                        alt=""
                        class="bg-muted max-h-[70vh] w-full rounded-xl border object-contain"
                    />
                </header>
                <dl
                    class="bg-card grid gap-5 rounded-xl border p-6 sm:grid-cols-2"
                >
                    <div>
                        <dt class="text-muted-foreground text-sm">
                            Starts · Manila time
                        </dt>
                        <dd class="mt-1">
                            {{ formatEventDate(event.starts_at) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">
                            Ends · Manila time
                        </dt>
                        <dd class="mt-1">
                            {{ formatEventDate(event.ends_at) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">Venue</dt>
                        <dd class="mt-1 break-words">{{ event.venue }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">
                            Registration deadline · Manila time
                        </dt>
                        <dd class="mt-1">
                            {{ formatEventDate(event.registration_deadline) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">
                            Attendee capacity
                        </dt>
                        <dd class="mt-1">{{ event.capacity ?? 'No limit' }}</dd>
                    </div>
                </dl>
                <RichTextContent
                    :html="event.description"
                    class="text-base leading-8 break-words"
                />
                <section
                    class="border-border space-y-6 border-t pt-8"
                    aria-labelledby="register-heading"
                >
                    <h2 id="register-heading" class="font-serif text-2xl">
                        Register for this Event
                    </h2>
                    <template v-if="registrationAvailability">
                        <p class="text-muted-foreground">
                            {{ registrationAvailability }}
                        </p>
                        <Button v-if="!page.props.auth.user" as-child
                            ><Link :href="login()"
                                >Sign in to register</Link
                            ></Button
                        >
                    </template>
                    <Form
                        v-else
                        v-bind="registerForEvent.form(event.id)"
                        class="space-y-5"
                        v-slot="{ errors, processing }"
                    >
                        <input
                            type="hidden"
                            name="submission_key"
                            :value="submissionKey"
                        />
                        <p class="text-muted-foreground text-sm">
                            Add everyone attending, including yourself if you
                            will attend. Family members and guests do not need
                            their own accounts.
                        </p>
                        <div
                            v-for="(_, index) in attendees"
                            :key="index"
                            class="grid gap-2"
                        >
                            <Label :for="`attendee-${index}`"
                                >Attendee {{ index + 1 }} name</Label
                            >
                            <div class="flex items-start gap-2">
                                <Input
                                    :id="`attendee-${index}`"
                                    v-model="attendees[index]"
                                    :name="`attendees[${index}][name]`"
                                    required
                                    maxlength="255"
                                    :disabled="processing"
                                /><Button
                                    type="button"
                                    variant="ghost"
                                    :disabled="
                                        processing || attendees.length === 1
                                    "
                                    @click="attendees.splice(index, 1)"
                                    >Remove</Button
                                >
                            </div>
                            <InputError
                                :message="errors[`attendees.${index}.name`]"
                            />
                        </div>
                        <InputError :message="errors.attendees" /><InputError
                            :message="errors.submission_key"
                        />
                        <div
                            v-if="event.price_centavos > 0"
                            class="bg-muted space-y-3 rounded-lg p-4"
                        >
                            <p class="font-medium">
                                {{ attendees.length }} attendees ·
                                {{
                                    formatEventMoney(
                                        event.price_centavos * attendees.length,
                                    )
                                }}
                                total
                            </p>
                            <p class="text-sm">
                                Places are reserved for 24 hours. Submit payment
                                details within that window. Staff must confirm
                                payment before tickets are issued.
                            </p>
                            <p class="text-sm whitespace-pre-wrap">
                                {{ event.payment_instructions }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="processing"
                                @click="attendees.push('')"
                                >Add attendee</Button
                            ><Button type="submit" :disabled="processing">{{
                                event.price_centavos > 0
                                    ? 'Reserve places for 24 hours'
                                    : 'Confirm free registration'
                            }}</Button>
                        </div>
                    </Form>
                </section>
            </article>
        </main>
    </div>
</template>
