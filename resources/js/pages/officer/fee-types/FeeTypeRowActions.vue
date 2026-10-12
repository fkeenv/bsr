<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useStaffPermissions } from '@/composables/useStaffPermissions';
import { Button } from '@/components/ui/button';
import { edit as editFeeType } from '@/routes/officer/fee-types';
import type { FeeType } from '@/types/fee-type';

defineProps<{
    feeType: FeeType;
}>();
const { allows } = useStaffPermissions();
</script>

<template>
    <div class="flex flex-wrap justify-end gap-2">
        <Button
            v-if="allows('fee-types.edit')"
            variant="outline"
            size="sm"
            as-child
        >
            <Link :href="editFeeType(feeType)">Edit</Link>
        </Button>
    </div>
</template>
