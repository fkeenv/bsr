<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import OfficerPaymentController from '@/actions/App/Http/Controllers/Officer/PaymentController';
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Payment } from '@/types/payment';

const props = defineProps<{
    payment: Payment;
}>();

const open = ref(false);

const canManage = computed(
    () =>
        props.payment.status === 'pending' ||
        props.payment.status === 'confirmed',
);

function closeDialog(): void {
    open.value = false;
}

function methodLabel(method: string): string {
    if (method === 'gcash') {
        return 'GCash';
    }

    if (method === 'maya') {
        return 'Maya';
    }

    return method.charAt(0).toUpperCase() + method.slice(1);
}
</script>

<template>
    <div class="flex justify-end">
        <Dialog v-if="canManage" v-model:open="open">
            <DialogTrigger as-child>
                <Button variant="outline" size="sm">Manage</Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Manage Payment</DialogTitle>
                    <DialogDescription>
                        ₱{{ payment.amount }} ·
                        {{ methodLabel(payment.method) }}
                        <template v-if="payment.property_label">
                            · {{ payment.property_label }}
                        </template>
                    </DialogDescription>
                </DialogHeader>

                <div v-if="payment.status === 'pending'" class="space-y-6">
                    <Form
                        v-bind="
                            OfficerPaymentController.confirm.form(payment.id)
                        "
                        :options="{ preserveScroll: true }"
                        @success="closeDialog"
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="processing"
                        >
                            Confirm Payment
                        </Button>
                    </Form>

                    <Form
                        v-bind="
                            OfficerPaymentController.reject.form(payment.id)
                        "
                        class="space-y-4"
                        :options="{ preserveScroll: true }"
                        @success="closeDialog"
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
                        <div class="flex flex-wrap justify-end gap-2">
                            <DialogClose as-child>
                                <Button type="button" variant="secondary">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                variant="outline"
                                :disabled="processing"
                            >
                                Reject
                            </Button>
                        </div>
                    </Form>
                </div>

                <Form
                    v-else-if="payment.status === 'confirmed'"
                    v-bind="OfficerPaymentController.void.form(payment.id)"
                    class="space-y-4"
                    :options="{ preserveScroll: true }"
                    @success="closeDialog"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label :for="`void_reason_${payment.id}`">
                            Void reason
                        </Label>
                        <Input
                            :id="`void_reason_${payment.id}`"
                            name="void_reason"
                            required
                        />
                        <InputError :message="errors.void_reason" />
                    </div>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                        >
                            Void Payment
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
