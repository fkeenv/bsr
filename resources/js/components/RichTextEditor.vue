<script setup lang="ts">
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Heading2,
    Heading3,
    Italic,
    List,
    ListOrdered,
    Quote,
    Redo2,
    Strikethrough,
    Undo2,
} from '@lucide/vue';
import { onBeforeUnmount, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        id?: string;
        disabled?: boolean;
    }>(),
    {
        modelValue: '',
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const revision = ref(0);

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [2, 3],
            },
        }),
    ],
    editable: !props.disabled,
    editorProps: {
        attributes: {
            class: cn(
                'prose-legal min-h-48 max-w-none px-3 py-2 text-sm outline-none',
                'focus-visible:outline-none',
            ),
            ...(props.id ? { id: props.id } : {}),
        },
    },
    onUpdate: ({ editor: current }) => {
        emit('update:modelValue', current.getHTML());
    },
    onTransaction: () => {
        revision.value += 1;
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) {
            return;
        }

        const next = value ?? '';

        if (next === editor.value.getHTML()) {
            return;
        }

        editor.value.commands.setContent(next, { emitUpdate: false });
    },
);

watch(
    () => props.disabled,
    (disabled) => {
        editor.value?.setEditable(!disabled);
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

function isActive(name: string, attributes?: Record<string, unknown>): boolean {
    void revision.value;

    return editor.value?.isActive(name, attributes) ?? false;
}
</script>

<template>
    <div
        class="border-input dark:bg-input/30 focus-within:border-ring focus-within:ring-ring/50 flex flex-col overflow-hidden rounded-md border bg-transparent shadow-xs focus-within:ring-[3px]"
    >
        <div
            v-if="editor"
            class="border-input bg-background flex shrink-0 flex-wrap gap-1 border-b p-1"
        >
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="{ 'bg-accent': isActive('bold') }"
                :disabled="disabled"
                aria-label="Bold"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="{ 'bg-accent': isActive('italic') }"
                :disabled="disabled"
                aria-label="Italic"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="{ 'bg-accent': isActive('strike') }"
                :disabled="disabled"
                aria-label="Strikethrough"
                @click="editor.chain().focus().toggleStrike().run()"
            >
                <Strikethrough />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="{ 'bg-accent': isActive('heading', { level: 2 }) }"
                :disabled="disabled"
                aria-label="Heading 2"
                @click="
                    editor.chain().focus().toggleHeading({ level: 2 }).run()
                "
            >
                <Heading2 />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="{ 'bg-accent': isActive('heading', { level: 3 }) }"
                :disabled="disabled"
                aria-label="Heading 3"
                @click="
                    editor.chain().focus().toggleHeading({ level: 3 }).run()
                "
            >
                <Heading3 />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="{ 'bg-accent': isActive('bulletList') }"
                :disabled="disabled"
                aria-label="Bullet list"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="{ 'bg-accent': isActive('orderedList') }"
                :disabled="disabled"
                aria-label="Ordered list"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :class="{ 'bg-accent': isActive('blockquote') }"
                :disabled="disabled"
                aria-label="Quote"
                @click="editor.chain().focus().toggleBlockquote().run()"
            >
                <Quote />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :disabled="disabled || !editor.can().undo()"
                aria-label="Undo"
                @click="editor.chain().focus().undo().run()"
            >
                <Undo2 />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :disabled="disabled || !editor.can().redo()"
                aria-label="Redo"
                @click="editor.chain().focus().redo().run()"
            >
                <Redo2 />
            </Button>
        </div>

        <div
            class="max-h-[min(32rem,60dvh)] min-h-48 overflow-y-auto overscroll-contain"
        >
            <EditorContent :editor="editor" />
        </div>
    </div>
</template>
