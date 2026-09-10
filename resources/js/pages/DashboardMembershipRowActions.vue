<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import MembershipController from '@/actions/App/Http/Controllers/MembershipController';
import { Button } from '@/components/ui/button';
import type { Membership } from '@/types/membership';

defineProps<{
    membership: Membership;
}>();
</script>

<template>
    <div class="flex flex-wrap justify-end gap-2">
        <Form
            v-bind="MembershipController.end.form(membership.id)"
            v-slot="{ processing }"
        >
            <Button
                type="submit"
                variant="outline"
                size="sm"
                :disabled="processing"
            >
                End Membership
            </Button>
        </Form>
    </div>
</template>
