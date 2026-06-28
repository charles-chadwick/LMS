<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const editorElement = ref(null);
let quill = null;
let isInternalChange = false;

onMounted(() => {
    quill = new Quill(editorElement.value, {
        theme: 'snow',
        placeholder: props.placeholder,
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'blockquote'],
                ['clean'],
            ],
        },
    });

    if (props.modelValue) {
        quill.clipboard.dangerouslyPasteHTML(props.modelValue);
    }

    quill.on('text-change', () => {
        isInternalChange = true;
        const html = quill.root.innerHTML;
        emit('update:modelValue', html === '<p><br></p>' ? '' : html);
    });
});

watch(
    () => props.modelValue,
    (newValue) => {
        if (isInternalChange) {
            isInternalChange = false;
            return;
        }
        if (quill && newValue !== quill.root.innerHTML) {
            quill.clipboard.dangerouslyPasteHTML(newValue || '');
        }
    },
);

onBeforeUnmount(() => {
    quill = null;
});
</script>

<template>
  <div class="quill-editor rounded-md border border-input bg-background">
    <div ref="editorElement"></div>
  </div>
</template>

<style scoped>
.quill-editor :deep(.ql-toolbar) {
    border: none;
    border-bottom: 1px solid var(--border);
    border-top-left-radius: calc(var(--radius) - 2px);
    border-top-right-radius: calc(var(--radius) - 2px);
}

.quill-editor :deep(.ql-container) {
    border: none;
    font-size: 0.95rem;
    min-height: 12rem;
}

.quill-editor :deep(.ql-editor) {
    min-height: 12rem;
}
</style>
