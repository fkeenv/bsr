<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import EventPaymentFields from '@/components/EventPaymentFields.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { eventDateInput } from '@/lib/eventDates';
import { eventCentavos, formatEventMoney } from '@/lib/eventMoney';
import { refund } from '@/routes/officer/event-payments';
defineProps<{ paymentId: number; remaining: number; submissionKey: string }>();
const amount = ref('');
</script>
<template>
    <Form
        v-bind="refund.form(paymentId)"
        :options="{ preserveScroll: true }"
        class="space-y-4"
        v-slot="{ errors, processing }"
    >
        <h3 class="font-medium">Record an external refund</h3>
        <p class="text-muted-foreground text-sm">
            Record money already returned outside this platform. Remaining:
            {{ formatEventMoney(remaining) }}.
        </p>
        <input type="hidden" name="submission_key" :value="submissionKey" />
        <input
            type="hidden"
            name="amount_centavos"
            :value="eventCentavos(amount)"
        />
        <div class="grid gap-2">
            <Label :for="`refund-amount-${paymentId}`"
                >Refund amount (PHP)</Label
            ><Input
                :id="`refund-amount-${paymentId}`"
                v-model="amount"
                type="number"
                min="0.01"
                :max="remaining / 100"
                step="0.01"
                required
            /><InputError :message="errors.amount_centavos" />
        </div>
        <EventPaymentFields
            :errors="errors"
            :id-prefix="`refund-${paymentId}`"
        />
        <div class="grid gap-2">
            <Label :for="`refunded-at-${paymentId}`"
                >Money returned at · Manila time</Label
            ><Input
                :id="`refunded-at-${paymentId}`"
                name="refunded_at"
                type="datetime-local"
                :default-value="eventDateInput(new Date().toISOString())"
                required
            /><InputError :message="errors.refunded_at" />
        </div>
        <div class="grid gap-2">
            <Label :for="`refund-reason-${paymentId}`">Refund reason</Label
            ><Textarea
                :id="`refund-reason-${paymentId}`"
                name="reason"
                required
                maxlength="10000"
            /><InputError :message="errors.reason" />
        </div>
        <InputError :message="errors.refund" /><InputError
            :message="errors.submission_key"
        />
        <Button type="submit" :disabled="processing">Record refund</Button>
    </Form>
</template>
