<script setup lang="ts">
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Heading2,
    Italic,
    List,
    ListOrdered,
    Redo2,
    Undo2,
} from '@lucide/vue';
import { onBeforeUnmount, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        id?: string;
        name?: string;
        defaultValue?: string;
        placeholder?: string;
        minHeight?: string;
    }>(),
    {
        minHeight: '140px',
    },
);

const htmlValue = ref(normalizeHtml(props.defaultValue ?? ''));

function normalizeHtml(value: string): string {
    const trimmed = value.trim();

    if (!trimmed || trimmed === '<p></p>') {
        return '';
    }

    return trimmed;
}

const editor = useEditor({
    content: props.defaultValue ?? '',
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
        }),
    ],
    editorProps: {
        attributes: {
            class: 'rich-text-content min-h-[inherit] px-3.5 py-2.5 focus:outline-none',
            ...(props.id ? { id: props.id } : {}),
            ...(props.placeholder
                ? { 'data-placeholder': props.placeholder }
                : {}),
        },
    },
    onUpdate: ({ editor: currentEditor }) => {
        htmlValue.value = normalizeHtml(currentEditor.getHTML());
    },
});

watch(
    () => props.defaultValue,
    (value) => {
        if (!editor.value || value === undefined) {
            return;
        }

        const next = value ?? '';

        if (normalizeHtml(editor.value.getHTML()) !== normalizeHtml(next)) {
            editor.value.commands.setContent(next, { emitUpdate: false });
            htmlValue.value = normalizeHtml(next);
        }
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

const run = (command: () => void): void => {
    command();
    editor.value?.commands.focus();
};

const selectClass = (active: boolean): string =>
    cn(
        'size-8 shrink-0 rounded-md text-muted-foreground hover:bg-muted hover:text-foreground',
        active && 'bg-muted text-foreground',
    );
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-input bg-background shadow-sm focus-within:ring-[3px] focus-within:ring-ring/30"
        :style="{ '--editor-min-height': minHeight }"
    >
        <div
            v-if="editor"
            class="flex flex-wrap items-center gap-0.5 border-b border-border bg-muted/40 px-2 py-1.5"
        >
            <Button
                type="button"
                variant="ghost"
                size="icon"
                :class="selectClass(editor.isActive('bold'))"
                @click="run(() => editor?.chain().focus().toggleBold().run())"
            >
                <Bold class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                :class="selectClass(editor.isActive('italic'))"
                @click="run(() => editor?.chain().focus().toggleItalic().run())"
            >
                <Italic class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                :class="selectClass(editor.isActive('heading', { level: 2 }))"
                @click="
                    run(() =>
                        editor?.chain().focus().toggleHeading({ level: 2 }).run(),
                    )
                "
            >
                <Heading2 class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                :class="selectClass(editor.isActive('bulletList'))"
                @click="run(() => editor?.chain().focus().toggleBulletList().run())"
            >
                <List class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                :class="selectClass(editor.isActive('orderedList'))"
                @click="run(() => editor?.chain().focus().toggleOrderedList().run())"
            >
                <ListOrdered class="size-4" />
            </Button>

            <div class="mx-1 h-5 w-px bg-border" />

            <Button
                type="button"
                variant="ghost"
                size="icon"
                :class="selectClass(false)"
                :disabled="!editor.can().undo()"
                @click="run(() => editor?.chain().focus().undo().run())"
            >
                <Undo2 class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                :class="selectClass(false)"
                :disabled="!editor.can().redo()"
                @click="run(() => editor?.chain().focus().redo().run())"
            >
                <Redo2 class="size-4" />
            </Button>
        </div>

        <EditorContent
            :editor="editor"
            class="min-h-[var(--editor-min-height)] [&_.ProseMirror]:min-h-[var(--editor-min-height)]"
        />

        <input v-if="name" type="hidden" :name="name" :value="htmlValue" />
    </div>
</template>

<style scoped>
:deep(.ProseMirror) {
    min-height: var(--editor-min-height);
}

:deep(.ProseMirror p.is-editor-empty:first-child::before) {
    color: var(--muted-foreground);
    content: attr(data-placeholder);
    float: inline-start;
    height: 0;
    pointer-events: none;
}
</style>
