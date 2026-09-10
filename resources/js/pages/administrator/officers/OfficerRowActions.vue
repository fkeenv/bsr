<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import OfficerController from '@/actions/App/Http/Controllers/Administrator/OfficerController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import type { PlatformRoleCandidate } from '@/types/platform-role-candidate';

defineProps<{
    candidate: PlatformRoleCandidate;
}>();
</script>

<template>
    <div class="flex flex-wrap justify-end gap-2">
        <Form
            v-if="!candidate.is_officer"
            v-bind="OfficerController.store.form()"
            class="flex items-center gap-2"
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="user_id" :value="candidate.id" />
            <Button type="submit" size="sm" :disabled="processing">
                Appoint Officer
            </Button>
            <InputError :message="errors.user_id" />
        </Form>
        <span v-else class="text-muted-foreground text-sm"
            >Already Officer</span
        >
    </div>
</template>
