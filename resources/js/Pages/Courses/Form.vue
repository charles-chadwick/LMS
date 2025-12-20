<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button, Card, InputText, Select } from 'primevue';

const props = defineProps({
    course: {
        type: Object,
        default: null,
    },
    status_options: {
        type: Array,
        required: true,
    },
});

const is_editing = computed(() => props.course !== null);

const form = useForm({
    status: props.course?.status || 'Draft',
    title: props.course?.title || '',
    code: props.course?.code || '',
});

const submit = () => {
    if (is_editing.value) {
        form.put(route('courses.update', props.course.id), {
            preserveScroll: true,
            onSuccess: () => {
                // Success message will be handled by flash messages
            },
        });
    } else {
        form.post(route('courses.store'), {
            preserveScroll: true,
            onSuccess: () => {
                // Success message will be handled by flash messages
            },
        });
    }
};

const cancel = () => {
    if (is_editing.value) {
        window.history.back();
    } else {
        window.location.href = route('courses.index');
    }
};
</script>

<template>
    <div class="min-h-screen bg-stone-50 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-stone-900">
                    {{ is_editing ? 'Edit Course' : 'Create New Course' }}
                </h1>
                <p class="mt-2 text-sm text-stone-600">
                    {{ is_editing ? 'Update course information' : 'Fill in the details to create a new course' }}
                </p>
            </div>

            <Card class="shadow-lg">
                <template #content>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Status Field -->
                            <div class="flex flex-col">
                                <label
                                    for="status"
                                    class="mb-2 font-semibold text-sm text-stone-700"
                                >
                                    Status
                                    <span class="text-red-500">*</span>
                                </label>
                                <Select
                                    id="status"
                                    v-model="form.status"
                                    :options="status_options"
                                    option-label="label"
                                    option-value="value"
                                    placeholder="Select a status"
                                    class="w-full"
                                    :class="{ 'border-red-500': form.errors.status }"
                                />
                                <small
                                    v-if="form.errors.status"
                                    class="text-red-500 mt-1"
                                >
                                    {{ form.errors.status }}
                                </small>
                            </div>

                            <!-- Title Field -->
                            <div class="flex flex-col">
                                <label
                                    for="title"
                                    class="mb-2 font-semibold text-sm text-stone-700"
                                >
                                    Course Title
                                    <span class="text-red-500">*</span>
                                </label>
                                <InputText
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Enter course title"
                                    class="w-full"
                                    :class="{ 'border-red-500': form.errors.title }"
                                />
                                <small
                                    v-if="form.errors.title"
                                    class="text-red-500 mt-1"
                                >
                                    {{ form.errors.title }}
                                </small>
                            </div>

                            <!-- Code Field -->
                            <div class="flex flex-col">
                                <label
                                    for="code"
                                    class="mb-2 font-semibold text-sm text-stone-700"
                                >
                                    Course Code
                                    <span class="text-red-500">*</span>
                                </label>
                                <InputText
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    placeholder="Enter course code (e.g., CS101)"
                                    class="w-full"
                                    :class="{ 'border-red-500': form.errors.code }"
                                />
                                <small
                                    v-if="form.errors.code"
                                    class="text-red-500 mt-1"
                                >
                                    {{ form.errors.code }}
                                </small>
                                <small class="text-stone-500 mt-1">
                                    A unique identifier for this course
                                </small>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-stone-200">
                            <Button
                                type="button"
                                label="Cancel"
                                severity="secondary"
                                outlined
                                @click="cancel"
                                :disabled="form.processing"
                                class="px-6"
                            />
                            <Button
                                type="submit"
                                :label="is_editing ? 'Update Course' : 'Create Course'"
                                :loading="form.processing"
                                :disabled="form.processing"
                                class="px-6"
                            />
                        </div>
                    </form>
                </template>
            </Card>
        </div>
    </div>
</template>

<style scoped>
/* Additional custom styles if needed */
</style>
