<script setup lang="ts">
import ContentImage from '@/components/ContentImage.vue';
import RichTextContent from '@/components/RichTextContent.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatEventDate } from '@/lib/eventDates';
import { formatEventMoney } from '@/lib/eventMoney';
import type { AssociationEvent } from '@/types/event';

defineProps<{ event: AssociationEvent }>();
</script>

<template>
    <div class="space-y-5">
        <Card>
            <CardHeader><CardTitle>Details</CardTitle></CardHeader>
            <CardContent class="space-y-4">
                <p class="font-medium">{{ event.title }}</p>
                <RichTextContent :html="event.description" />
                <p>
                    <span class="text-muted-foreground">Venue:</span>
                    {{ event.venue }}
                </p>
                <ContentImage
                    v-if="event.image_url"
                    :src="event.image_url"
                    alt="Event image"
                    class="max-w-md rounded-lg"
                    fit="contain"
                />
                <p v-else class="text-muted-foreground text-sm">
                    No Event image provided.
                </p>
            </CardContent>
        </Card>
        <Card>
            <CardHeader><CardTitle>Schedule</CardTitle></CardHeader>
            <CardContent>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground text-sm">Starts</dt>
                        <dd>{{ formatEventDate(event.starts_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">Ends</dt>
                        <dd>{{ formatEventDate(event.ends_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">
                            Registration deadline
                        </dt>
                        <dd>
                            {{ formatEventDate(event.registration_deadline) }}
                        </dd>
                    </div>
                </dl>
                <p class="text-muted-foreground mt-4 text-sm">
                    All times in Manila time.
                </p>
            </CardContent>
        </Card>
        <Card>
            <CardHeader><CardTitle>Attendance</CardTitle></CardHeader>
            <CardContent>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground text-sm">Audience</dt>
                        <dd>{{ event.audience_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-sm">
                            Attendee capacity
                        </dt>
                        <dd>
                            {{
                                event.capacity === null
                                    ? 'No limit'
                                    : event.capacity
                            }}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>
        <Card>
            <CardHeader><CardTitle>Price &amp; payment</CardTitle></CardHeader>
            <CardContent class="space-y-4">
                <p>
                    {{
                        event.price_centavos === 0
                            ? 'Free Event'
                            : `${formatEventMoney(event.price_centavos)} per attendee`
                    }}
                </p>
                <div v-if="event.price_centavos > 0">
                    <p class="text-muted-foreground text-sm">
                        External payment instructions
                    </p>
                    <p class="whitespace-pre-wrap">
                        {{
                            event.payment_instructions ||
                            'No payment instructions provided.'
                        }}
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
