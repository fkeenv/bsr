<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import EventFinancialHistory from '@/components/EventFinancialHistory.vue';
import EventRefundForm from '@/components/EventRefundForm.vue';
import InputError from '@/components/InputError.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatEventMoney } from '@/lib/eventMoney';
import { resolve } from '@/routes/officer/event-cancellations';
import { review } from '@/routes/officer/event-payments';
import type { EventFinanceRow } from '@/types/event-finance';

defineProps<{
    item: EventFinanceRow;
    canReviewPayments: boolean;
    canReviewRefunds: boolean;
}>();
const open = ref(false);
function closeDialog() {
    open.value = false;
}
</script>
<template>
    <div class="flex justify-end">
        <Dialog v-model:open="open">
            <DialogTrigger as-child
                ><Button variant="outline" size="sm"
                    >Manage</Button
                ></DialogTrigger
            >
            <DialogContent
                class="max-h-[85dvh] overflow-y-auto overscroll-contain sm:max-w-2xl"
            >
                <DialogHeader>
                    <DialogTitle>Manage Event registration</DialogTitle>
                    <DialogDescription
                        >{{ item.registration.event.title }} ·
                        {{ item.registration.account_name }} · Registration #{{
                            item.id
                        }}
                        ·
                        {{
                            formatEventMoney(item.registration.total_centavos)
                        }}</DialogDescription
                    >
                </DialogHeader>
                <div
                    v-if="item.financial.payment"
                    class="bg-muted/50 space-y-3 rounded-lg border p-4 text-sm"
                >
                    <p>
                        <span class="font-medium">Payment:</span>
                        {{
                            formatEventMoney(
                                item.financial.payment.amount_centavos,
                            )
                        }}
                        ·
                        <span class="capitalize">{{
                            item.financial.payment.status
                        }}</span>
                    </p>
                    <p class="break-words">
                        <span class="font-medium">Reference:</span>
                        {{ item.financial.payment.reference }}
                    </p>
                    <a
                        v-if="item.financial.payment.receipt_url"
                        :href="item.financial.payment.receipt_url"
                        target="_blank"
                        rel="noopener"
                        class="font-medium underline underline-offset-4"
                        >View payment receipt</a
                    >
                    <dl class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">
                                Refund still due
                            </dt>
                            <dd class="font-medium tabular-nums">
                                {{
                                    formatEventMoney(
                                        item.financial.payment
                                            .refund_due_centavos,
                                    )
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                Refunds recorded
                            </dt>
                            <dd class="font-medium tabular-nums">
                                {{
                                    formatEventMoney(
                                        item.financial.payment
                                            .refunded_centavos,
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>
                </div>
                <p v-else class="text-muted-foreground text-sm">
                    No Event payment has been submitted.
                </p>
                <Form
                    v-if="
                        canReviewPayments &&
                        item.financial.payment &&
                        item.work.includes('payment-review')
                    "
                    v-bind="review.form(item.financial.payment.id)"
                    :options="{ preserveScroll: true }"
                    @success="closeDialog"
                    class="max-w-xl space-y-4"
                    v-slot="{ errors, processing }"
                >
                    <h3 class="font-medium">Review payment</h3>
                    <p class="text-muted-foreground text-sm">
                        Confirmation issues tickets for active registrations.
                        Cancelled registrations stay cancelled. Rejection
                        cancels the registration and releases all places.
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
                            :disabled="processing"
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
                        @success="closeDialog"
                        class="max-w-xl space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <h3 class="font-medium">Review cancellation request</h3>
                        <p class="text-muted-foreground text-sm">
                            Approval cancels every attendee ticket. It does not
                            record a refund.
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
                        @success="closeDialog"
                        v-if="
                            item.financial.payment &&
                            item.work.includes('refund-due')
                        "
                        :payment-id="item.financial.payment.id"
                        :remaining="item.financial.payment.refund_due_centavos"
                        :submission-key="item.submission_key"
                        class="max-w-xl"
                    />
                </template>
                <Accordion type="single" collapsible>
                    <AccordionItem value="history">
                        <AccordionTrigger
                            >Payment &amp; cancellation
                            history</AccordionTrigger
                        >
                        <AccordionContent
                            ><EventFinancialHistory :financial="item.financial"
                        /></AccordionContent>
                    </AccordionItem>
                </Accordion>
                <DialogFooter
                    ><DialogClose as-child
                        ><Button variant="secondary">Close</Button></DialogClose
                    ></DialogFooter
                >
            </DialogContent>
        </Dialog>
    </div>
</template>
