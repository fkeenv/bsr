<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { show as membershipApplicationShow } from '@/routes/officer/membership-applications';

defineProps<{
    applicationId: number;
}>();
</script>

<template>
    <div class="flex flex-wrap justify-end gap-2">
        <Button variant="outline" size="sm" as-child>
            <Link :href="membershipApplicationShow(applicationId)">Review</Link>
        </Button>
    </div>
</template>
