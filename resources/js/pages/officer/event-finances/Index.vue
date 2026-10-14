<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import EventFinancialHistory from '@/components/EventFinancialHistory.vue';
import EventRefundForm from '@/components/EventRefundForm.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatEventMoney } from '@/lib/eventMoney';
import { index } from '@/routes/officer/event-finances';
import { resolve } from '@/routes/officer/event-cancellations';
import { review } from '@/routes/officer/event-payments';
import type {
    EventFinancial,
    EventRegistration,
} from '@/types/event-registration';
defineProps<{
    registrations: {
        registration: EventRegistration;
        financial: EventFinancial;
        submission_key: string;
    }[];
    canReviewPayments: boolean;
    canReviewRefunds: boolean;
}>();
setLayoutProps({
    breadcrumbs: [{ title: 'Event payments & refunds', href: index() }],
});
</script>
<template>
    <div class="space-y-6 p-4 sm:p-6">
        <Head title="Event payments & refunds" />
        <Heading
            title="Event payments & refunds"
            description="Review external Event payments and whole-registration cancellations. These records are separate from property dues."
        />
        <p
            v-if="!registrations.length"
            class="text-muted-foreground rounded-xl border p-6"
        >
            No paid Event registrations yet.
        </p>
        <Accordion
            v-else
            type="multiple"
            class="bg-card rounded-xl border px-5"
        >
            <AccordionItem
                v-for="item in registrations"
                :key="item.registration.id"
                :value="String(item.registration.id)"
            >
                <AccordionTrigger class="items-center text-left">
                    <div class="space-y-2">
                        <p class="font-medium">
                            {{ item.registration.event.title }} ·
                            {{ item.registration.account_name }}
                        </p>
                        <p class="text-muted-foreground text-sm">
                            Registration #{{ item.registration.id }} ·
                            {{ item.registration.attendee_count }} attendees ·
                            {{
                                formatEventMoney(
                                    item.registration.total_centavos,
                                )
                            }}
                        </p>
                        <Badge variant="outline">{{
                            item.registration.status_label
                        }}</Badge>
                    </div>
                </AccordionTrigger>
                <AccordionContent class="space-y-6">
                    <EventFinancialHistory :financial="item.financial" />
                    <Form
                        v-if="
                            canReviewPayments &&
                            item.financial.payment?.status === 'submitted' &&
                            ['awaiting-review', 'cancelled'].includes(
                                item.registration.status,
                            )
                        "
                        v-bind="review.form(item.financial.payment.id)"
                        :options="{ preserveScroll: true }"
                        class="max-w-xl space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <h3 class="font-medium">Review payment</h3>
                        <p class="text-muted-foreground text-sm">
                            Confirmation issues tickets for active
                            registrations. Cancelled registrations stay
                            cancelled. Rejection cancels the registration and
                            releases all places.
                        </p>
                        <Label :for="`review-reason-${item.registration.id}`"
                            >Reason (required when rejecting)</Label
                        ><Textarea
                            :id="`review-reason-${item.registration.id}`"
                            name="reason"
                            maxlength="10000"
                        /><InputError :message="errors.reason" /><InputError
                            :message="errors.payment"
                        /><InputError :message="errors.decision" />
                        <div class="flex flex-wrap gap-3">
                            <Button
                                type="submit"
                                name="decision"
                                value="confirmed"
                                :disabled="
                                    processing ||
                                    item.registration.event.status ===
                                        'cancelled'
                                "
                                >Confirm payment</Button
                            ><Button
                                type="submit"
                                name="decision"
                                value="rejected"
                                variant="destructive"
                                :disabled="processing"
                                >Reject payment</Button
                            >
                        </div>
                    </Form>
                    <template v-if="canReviewRefunds">
                        <Form
                            v-for="request in item.financial.cancellations.filter(
                                (request) => request.status === 'pending',
                            )"
                            :key="request.id"
                            v-bind="resolve.form(request.id)"
                            :options="{ preserveScroll: true }"
                            class="max-w-xl space-y-4"
                            v-slot="{ errors, processing }"
                        >
                            <h3 class="font-medium">
                                Review cancellation request
                            </h3>
                            <p class="text-muted-foreground text-sm">
                                Approval cancels every attendee ticket. It does
                                not record a refund.
                            </p>
                            <Label :for="`resolution-reason-${request.id}`"
                                >Decision reason</Label
                            ><Textarea
                                :id="`resolution-reason-${request.id}`"
                                name="reason"
                                required
                                maxlength="10000"
                            /><InputError :message="errors.reason" /><InputError
                                :message="errors.cancellation"
                            /><InputError :message="errors.decision" />
                            <div class="flex flex-wrap gap-3">
                                <Button
                                    type="submit"
                                    name="decision"
                                    value="approved"
                                    :disabled="processing"
                                    >Approve cancellation</Button
                                ><Button
                                    type="submit"
                                    name="decision"
                                    value="declined"
                                    variant="outline"
                                    :disabled="processing"
                                    >Decline cancellation</Button
                                >
                            </div>
                        </Form>
                        <EventRefundForm
                            v-if="
                                item.registration.cancelled_at &&
                                item.financial.payment?.status ===
                                    'confirmed' &&
                                item.financial.payment.amount_centavos >
                                    item.financial.payment.refunded_centavos
                            "
                            :payment-id="item.financial.payment.id"
                            :remaining="
                                item.financial.payment.amount_centavos -
                                item.financial.payment.refunded_centavos
                            "
                            :submission-key="item.submission_key"
                            class="max-w-xl"
                        />
                    </template>
                </AccordionContent>
            </AccordionItem>
        </Accordion>
    </div>
</template>
