<script setup lang="ts">
import { Check, ChevronsUpDown } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxList,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxTrigger,
    ComboboxViewport,
} from '@/components/ui/combobox';
import { Button } from '@/components/ui/button';
import type { PropertyInvitationPropertyOption } from '@/types/property-invitation';

const props = defineProps<{
    id: string;
    properties: PropertyInvitationPropertyOption[];
}>();
const model = defineModel<number | ''>({ required: true });
const search = ref('');
const open = ref(false);
const selection = computed({
    get: () => (model.value === '' ? undefined : model.value),
    set: (value: number | undefined | null) => {
        model.value = value ?? '';
    },
});
const filteredProperties = computed(() => {
    const words = search.value.trim().toLowerCase().split(/\s+/);
    return props.properties.filter((property) =>
        words.every((word) => property.label.toLowerCase().includes(word)),
    );
});
function displayValue(value: number | undefined): string {
    return (
        props.properties.find((property) => property.id === value)?.label ?? ''
    );
}
watch(open, (value) => {
    if (!value) {
        search.value = '';
    }
});
watch(model, (value) => {
    if (value === '') {
        search.value = '';
    }
});
</script>

<template>
    <Combobox
        v-model="selection"
        v-model:open="open"
        ignore-filter
        :disabled="properties.length === 0"
    >
        <ComboboxAnchor class="w-full">
            <ComboboxTrigger as-child>
                <Button
                    :id="id"
                    type="button"
                    variant="outline"
                    class="w-full justify-between font-normal"
                    :disabled="properties.length === 0"
                    :aria-expanded="open"
                >
                    <span class="truncate">{{
                        displayValue(selection) || 'Select a Property'
                    }}</span>
                    <ChevronsUpDown
                        class="size-4 shrink-0 opacity-50"
                        aria-hidden="true"
                    />
                </Button>
            </ComboboxTrigger>
        </ComboboxAnchor>
        <ComboboxList
            align="start"
            class="w-(--reka-combobox-trigger-width) min-w-64"
        >
            <ComboboxInput
                v-model="search"
                :display-value="() => ''"
                auto-focus
                aria-label="Search Properties"
                placeholder="Search by block or lot…"
                autocomplete="off"
                :disabled="properties.length === 0"
            />
            <ComboboxViewport>
                <p
                    v-if="filteredProperties.length === 0"
                    role="status"
                    class="text-muted-foreground p-3 text-sm"
                >
                    No Properties match your search.
                </p>
                <ComboboxItem
                    v-for="property in filteredProperties"
                    :key="property.id"
                    :value="property.id"
                >
                    {{ property.label }}
                    <ComboboxItemIndicator
                        ><Check class="size-4" aria-hidden="true"
                    /></ComboboxItemIndicator>
                </ComboboxItem>
            </ComboboxViewport>
        </ComboboxList>
    </Combobox>
</template>
