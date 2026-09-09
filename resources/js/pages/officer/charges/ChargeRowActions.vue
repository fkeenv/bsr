<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { edit as editCharge } from '@/routes/officer/charges';
import type { Charge } from '@/types/charge';

defineProps<{
    charge: Charge;
}>();
</script>

<template>
    <div class="flex flex-wrap justify-end gap-2">
        <Button variant="outline" size="sm" as-child>
            <Link :href="editCharge(charge)">Edit</Link>
        </Button>
    </div>
</template>
