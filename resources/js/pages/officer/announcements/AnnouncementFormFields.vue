<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AnnouncementVisibilityOption } from '@/types/announcement';

const props = withDefaults(
    defineProps<{
        visibility?: 'public' | 'private' | 'hidden';
        visibilityOptions: AnnouncementVisibilityOption[];
        title?: string;
        body: string;
        errors?: Record<string, string>;
    }>(),
    {
        title: '',
        visibility: 'private',
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
        <fieldset class="space-y-3">
            <legend class="mb-2 font-medium">Visibility</legend>
            <p class="text-muted-foreground text-sm">
                Choose who can read this Announcement once published. Drafts are
                always shared only with Officers.
            </p>
            <label
                v-for="option in visibilityOptions"
                :key="option.value"
                class="flex min-h-11 cursor-pointer items-start gap-3 rounded-lg border p-3"
            >
                <input
                    type="radio"
                    name="visibility"
                    :value="option.value"
                    :checked="visibility === option.value"
                    required
                    class="accent-primary mt-1 size-4"
                />
                <span class="space-y-1">
                    <span class="block text-sm font-medium">{{
                        option.label
                    }}</span>
                    <span class="text-muted-foreground block text-sm">{{
                        option.description
                    }}</span>
                </span>
            </label>
            <div v-if="errors.visibility" role="alert">
                <InputError :message="errors.visibility" />
            </div>
        </fieldset>

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
