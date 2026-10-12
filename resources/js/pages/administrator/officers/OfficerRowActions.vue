<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import OfficerController from '@/actions/App/Http/Controllers/Administrator/OfficerController';
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
import type { PlatformRoleCandidate } from '@/types/platform-role-candidate';

const props = defineProps<{ candidate: PlatformRoleCandidate }>();
const page = usePage();
const open = ref(false);
const isOwnAccount = computed(
    () => page.props.auth.user?.id === props.candidate.id,
);
const canAppoint = computed(
    () =>
        !props.candidate.is_officer &&
        page.props.auth.capabilities?.canAccessAdministrator &&
        (!isOwnAccount.value || page.props.auth.capabilities?.isSuperAdmin),
);
</script>

<template>
    <div class="flex justify-end">
        <Dialog v-if="canAppoint" v-model:open="open">
            <DialogTrigger as-child
                ><Button variant="outline" size="sm"
                    >Manage</Button
                ></DialogTrigger
            >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle
                        >Appoint {{ candidate.name }} as Officer</DialogTitle
                    >
                    <DialogDescription
                        >This adds the Officer staff role and its configured
                        permissions. The account must retain a live Owner
                        Membership.</DialogDescription
                    >
                </DialogHeader>
                <Form
                    v-bind="OfficerController.store.form()"
                    :options="{ preserveScroll: true }"
                    v-slot="{ errors, processing }"
                    @success="open = false"
                >
                    <input type="hidden" name="user_id" :value="candidate.id" />
                    <InputError :message="errors.user_id" />
                    <DialogFooter>
                        <DialogClose as-child
                            ><Button
                                type="button"
                                variant="secondary"
                                :disabled="processing"
                                >Cancel</Button
                            ></DialogClose
                        >
                        <Button type="submit" :disabled="processing"
                            >Appoint Officer</Button
                        >
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
        <span
            v-else-if="candidate.is_officer"
            class="text-muted-foreground text-sm"
            >Already Officer</span
        >
        <span v-else-if="isOwnAccount" class="text-muted-foreground text-sm"
            >Super Admin manages your staff roles.</span
        >
    </div>
</template>
