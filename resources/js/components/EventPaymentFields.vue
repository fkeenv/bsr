<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
withDefaults(
    defineProps<{
        errors: Record<string, string>;
        receipt?: boolean;
        idPrefix?: string;
    }>(),
    { idPrefix: 'event-payment' },
);
</script>
<template>
    <div class="space-y-4">
        <div class="grid gap-2">
            <Label :for="`${idPrefix}-method`">Payment method</Label
            ><Select name="method" default-value="gcash"
                ><SelectTrigger :id="`${idPrefix}-method`"
                    ><SelectValue /></SelectTrigger
                ><SelectContent
                    ><SelectItem value="cash">Cash</SelectItem
                    ><SelectItem value="gcash">GCash</SelectItem
                    ><SelectItem value="bank-transfer"
                        >Bank transfer</SelectItem
                    ></SelectContent
                ></Select
            ><InputError :message="errors.method" />
        </div>
        <div class="grid gap-2">
            <Label :for="`${idPrefix}-reference`"
                >Reference / payment details</Label
            ><Textarea
                :id="`${idPrefix}-reference`"
                name="reference"
                required
                maxlength="1000"
                placeholder="Transaction reference, or who received your cash and when."
            /><InputError :message="errors.reference" />
        </div>
        <div v-if="receipt" class="grid gap-2">
            <Label :for="`${idPrefix}-receipt`">Receipt image (optional)</Label
            ><Input
                :id="`${idPrefix}-receipt`"
                type="file"
                name="receipt"
                accept="image/jpeg,image/png,image/webp"
            />
            <p class="text-muted-foreground text-sm">
                JPEG, PNG or WebP, up to 10 MB. Only you and financial reviewers
                can open it.
            </p>
            <InputError :message="errors.receipt" />
        </div>
    </div>
</template>
