<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PropertyController from '@/actions/App/Http/Controllers/Officer/PropertyController';
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
import { edit as editProperty } from '@/routes/officer/properties';
import type { Property } from '@/types/property';

defineProps<{
    property: Property;
}>();

const open = ref(false);
const processing = ref(false);

function updateOpen(value: boolean): void {
    if (!processing.value) {
        open.value = value;
    }
}

function canSubmit(): boolean {
    return !processing.value;
}

function closeDialog(): void {
    open.value = false;
}
</script>

<template>
    <div class="flex justify-end">
        <Dialog :open="open" @update:open="updateOpen">
            <DialogTrigger as-child>
                <Button variant="outline" size="sm">Manage</Button>
            </DialogTrigger>
            <DialogContent :show-close-button="!processing" class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Manage Property</DialogTitle>
                    <DialogDescription>
                        Block {{ property.block }} · Lot {{ property.lot }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-6">
                    <Button v-if="processing" variant="outline" disabled>
                        Edit
                    </Button>
                    <Button v-else variant="outline" as-child>
                        <Link :href="editProperty(property)">Edit</Link>
                    </Button>

                    <Form
                        v-bind="
                            property.is_active
                                ? PropertyController.deactivate.form(property)
                                : PropertyController.activate.form(property)
                        "
                        class="space-y-2"
                        :options="{ preserveScroll: true }"
                        :on-before="canSubmit"
                        @start="processing = true"
                        @finish="processing = false"
                        @success="closeDialog"
                        v-slot="{ errors, processing: submitting }"
                    >
                        <InputError
                            v-for="(message, key) in errors"
                            :key="key"
                            :message="message"
                        />
                        <Button
                            type="submit"
                            variant="outline"
                            class="w-full"
                            :disabled="processing"
                        >
                            {{
                                submitting
                                    ? property.is_active
                                        ? 'Deactivating...'
                                        : 'Activating...'
                                    : property.is_active
                                      ? 'Deactivate'
                                      : 'Activate'
                            }}
                        </Button>
                    </Form>

                    <Form
                        v-if="!property.has_been_charged"
                        v-bind="PropertyController.destroy.form(property)"
                        class="space-y-2"
                        :options="{ preserveScroll: true }"
                        :on-before="canSubmit"
                        @start="processing = true"
                        @finish="processing = false"
                        @success="closeDialog"
                        v-slot="{ errors, processing: submitting }"
                    >
                        <InputError
                            v-for="(message, key) in errors"
                            :key="key"
                            :message="message"
                        />
                        <Button
                            type="submit"
                            variant="destructive"
                            class="w-full"
                            :disabled="processing"
                        >
                            {{ submitting ? 'Deleting...' : 'Delete' }}
                        </Button>
                    </Form>
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="secondary"
                            :disabled="processing"
                        >
                            Cancel
                        </Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
