<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import OfficerPaymentController from '@/actions/App/Http/Controllers/Officer/PaymentController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Payment } from '@/types/payment';

defineProps<{
    payment: Payment;
}>();
</script>

<template>
    <div class="flex flex-col items-end gap-2">
        <Form
            v-if="payment.status === 'pending'"
            v-bind="OfficerPaymentController.confirm.form(payment.id)"
            v-slot="{ processing }"
        >
            <Button type="submit" size="sm" :disabled="processing">
                Confirm
            </Button>
        </Form>

        <Form
            v-if="payment.status === 'pending'"
            v-bind="OfficerPaymentController.reject.form(payment.id)"
            class="flex flex-wrap items-end justify-end gap-2"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label :for="`rejection_reason_${payment.id}`">
                    Reject reason
                </Label>
                <Input
                    :id="`rejection_reason_${payment.id}`"
                    name="rejection_reason"
                    required
                />
                <InputError :message="errors.rejection_reason" />
            </div>
            <Button
                type="submit"
                variant="outline"
                size="sm"
                :disabled="processing"
            >
                Reject
            </Button>
        </Form>

        <Form
            v-if="payment.status === 'confirmed'"
            v-bind="OfficerPaymentController.void.form(payment.id)"
            class="flex flex-wrap items-end justify-end gap-2"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label :for="`void_reason_${payment.id}`">Void reason</Label>
                <Input
                    :id="`void_reason_${payment.id}`"
                    name="void_reason"
                    required
                />
                <InputError :message="errors.void_reason" />
            </div>
            <Button
                type="submit"
                variant="destructive"
                size="sm"
                :disabled="processing"
            >
                Void
            </Button>
        </Form>
    </div>
</template>
