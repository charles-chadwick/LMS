<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    course: {
        type: Object,
        required: true,
    },
    pages: {
        type: Array,
        required: true,
    },
    current_page: {
        type: Object,
        default: null,
    },
    progress: {
        type: Object,
        required: true,
    },
    is_complete: {
        type: Boolean,
        required: true,
    },
    completed_at: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <AppLayout>
        <div>
            <h1>{{ course.title }}</h1>
        </div>
    </AppLayout>
</template>
