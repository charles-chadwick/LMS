<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    course: {
        type: Object,
        required: true,
    },
    student: {
        type: Object,
        required: true,
    },
    completed_at: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <AppLayout>
        <div>Certificate placeholder</div>
    </AppLayout>
</template>
