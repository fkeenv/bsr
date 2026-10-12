<script setup lang="ts">
import { useStaffPermissions } from '@/composables/useStaffPermissions';

import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { edit as editSuspend } from '@/routes/officer/suspends';
import type { Suspend } from '@/types/suspend';

const { allows } = useStaffPermissions();

defineProps<{
    suspend: Suspend;
}>();
</script>

<template>
    <div class="flex flex-wrap justify-end gap-2">
        <Button
            v-if="allows('suspends.edit')"
            variant="outline"
            size="sm"
            as-child
        >
            <Link :href="editSuspend(suspend)">Edit</Link>
        </Button>
    </div>
</template>
