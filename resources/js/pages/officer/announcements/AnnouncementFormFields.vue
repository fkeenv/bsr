<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = withDefaults(
    defineProps<{
        title?: string;
        body: string;
        errors?: Record<string, string>;
    }>(),
    {
        title: '',
        errors: () => ({}),
    },
);

const emit = defineEmits<{
    'update:body': [value: string];
}>();

const bodyModel = computed({
    get: () => props.body,
    set: (value: string) => emit('update:body', value),
});
</script>

<template>
    <div class="space-y-6">
        <div class="grid gap-2">
            <Label for="title">Title</Label>
            <Input id="title" name="title" required :default-value="title" />
            <InputError :message="errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="body">Body</Label>
            <input type="hidden" name="body" :value="bodyModel" />
            <RichTextEditor id="body" v-model="bodyModel" />
            <InputError :message="errors.body" />
            <p class="text-muted-foreground text-xs">
                Use the rich text editor for headings, lists, and emphasis.
            </p>
        </div>
    </div>
</template>
