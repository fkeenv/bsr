<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { formatEventDate } from '@/lib/eventDates';
import { formatEventMoney } from '@/lib/eventMoney';
import type { EventFinancial } from '@/types/event-registration';
defineProps<{ financial: EventFinancial }>();
</script>
<template>
    <div class="space-y-6">
        <section
            v-if="financial.payment"
            class="bg-card space-y-3 rounded-xl border p-5"
            aria-label="Payment history"
        >
            <h2 class="font-serif text-2xl">Payment record</h2>
            <Badge variant="outline">{{ financial.payment.status }}</Badge>
            <p>
                {{ formatEventMoney(financial.payment.amount_centavos) }} ·
                {{ financial.payment.method }}
            </p>
            <p class="break-words">
                Reference / details: {{ financial.payment.reference }}
            </p>
            <p class="text-muted-foreground text-sm">
                Submitted {{ formatEventDate(financial.payment.submitted_at) }}
            </p>
            <a
                v-if="financial.payment.receipt_url"
                :href="financial.payment.receipt_url"
                target="_blank"
                rel="noopener noreferrer"
                class="text-primary inline-block font-medium underline underline-offset-4"
                >View private receipt</a
            >
            <p
                v-if="financial.payment.reviewed_at"
                class="text-muted-foreground text-sm"
            >
                Reviewed {{ formatEventDate(financial.payment.reviewed_at) }} ·
                Staff account #{{ financial.payment.reviewed_by_user_id }}
            </p>
            <p
                v-if="financial.payment.review_reason"
                class="whitespace-pre-wrap"
            >
                {{ financial.payment.review_reason }}
            </p>
            <p
                v-if="financial.payment.refund_due_centavos > 0"
                class="bg-muted rounded-lg p-4 font-medium"
            >
                Refund required:
                {{ formatEventMoney(financial.payment.refund_due_centavos) }}.
                This amount has not been recorded as returned. Staff must record
                the external refund separately.
            </p>
            <p v-if="financial.payment.refunds.length" class="font-medium">
                Refunds recorded:
                {{ formatEventMoney(financial.payment.refunded_centavos) }}
            </p>
            <article
                v-for="refund in financial.payment.refunds"
                :key="refund.id"
                class="border-border space-y-2 border-t pt-3 text-sm"
            >
                <p>
                    {{ formatEventMoney(refund.amount_centavos) }} returned via
                    {{ refund.method }} ·
                    {{ formatEventDate(refund.refunded_at) }}
                </p>
                <p class="break-words">Reference: {{ refund.reference }}</p>
                <p class="whitespace-pre-wrap">{{ refund.reason }}</p>
                <p class="text-muted-foreground">
                    Recorded {{ formatEventDate(refund.recorded_at) }} · Staff
                    account #{{ refund.recorded_by_user_id }}
                </p>
            </article>
        </section>
        <section
            v-if="financial.cancellations.length"
            class="bg-card space-y-4 rounded-xl border p-5"
            aria-label="Cancellation history"
        >
            <h2 class="font-serif text-2xl">Cancellation requests</h2>
            <p class="text-muted-foreground text-sm">
                Approval releases places and invalidates tickets. Money returned
                is recorded separately.
            </p>
            <article
                v-for="request in financial.cancellations"
                :key="request.id"
                class="border-border space-y-2 border-t pt-3"
            >
                <Badge variant="outline">{{ request.status }}</Badge>
                <p class="whitespace-pre-wrap">{{ request.reason }}</p>
                <p class="text-muted-foreground text-sm">
                    Requested {{ formatEventDate(request.requested_at) }}
                </p>
                <p
                    v-if="request.resolved_at"
                    class="text-muted-foreground text-sm"
                >
                    Resolved {{ formatEventDate(request.resolved_at) }} · Staff
                    account #{{ request.resolved_by_user_id }}
                </p>
                <p v-if="request.resolution_reason" class="whitespace-pre-wrap">
                    {{ request.resolution_reason }}
                </p>
            </article>
        </section>
    </div>
</template>
