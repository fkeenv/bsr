<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { edit as editFeeType } from '@/routes/officer/fee-types';
import type { FeeType } from '@/types/fee-type';

defineProps<{
    feeType: FeeType;
}>();
</script>

<template>
    <div class="flex flex-wrap justify-end gap-2">
        <Button variant="outline" size="sm" as-child>
            <Link :href="editFeeType(feeType)">Edit</Link>
        </Button>
    </div>
</template>
