<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Editor from 'primevue/editor';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    page: {
        type: Object,
        default: null,
    },
    courses: {
        type: Array,
        required: true,
    },
    status_options: {
        type: Array,
        required: true,
    },
});

const is_editing = computed(() => props.page !== null);

const preselected_course_id = Number(
    new URLSearchParams(window.location.search).get('course_id'),
) || null;

const form = useForm({
    course_id: props.page?.course_id || preselected_course_id,
    status: props.page?.status || 'Draft',
    title: props.page?.title || '',
    content: props.page?.content || '',
});

const submit = () => {
    if (is_editing.value) {
        form.put(route('pages.update', props.page.id), {
            preserveScroll: true,
        });
    } else {
        form.post(route('pages.store'), {
            preserveScroll: true,
        });
    }
};

const cancel = () => {
    if (form.course_id) {
        window.location.href = route('courses.show', form.course_id);
    } else {
        window.location.href = route('courses.index');
    }
};
</script>

<template>
  <AppLayout>
    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <div class="mb-6">
        <h1 class="text-3xl font-bold text-darker-900">
          {{ is_editing ? 'Edit Page' : 'Create New Page' }}
        </h1>
        <p class="mt-2 text-sm text-darker-600">
          {{ is_editing ? 'Update page content' : 'Fill in the details to add a page to a course' }}
        </p>
      </div>

      <Card class="shadow-lg">
        <template #content>
          <form
              @submit.prevent="submit"
              class="space-y-6"
          >
            <div class="grid grid-cols-1 gap-6">
              <!-- Course Field -->
              <div class="flex flex-col">
                <label
                    for="course_id"
                    class="mb-2 font-semibold text-sm text-darker-700"
                >
                  Course
                  <span class="text-red-500">*</span>
                </label>
                <Select
                    id="course_id"
                    v-model="form.course_id"
                    :options="courses"
                    option-label="title"
                    option-value="id"
                    placeholder="Select a course"
                    filter
                    class="w-full"
                    :invalid="!!form.errors.course_id"
                />
                <small
                    v-if="form.errors.course_id"
                    class="text-red-600 mt-1 block"
                >
                  {{ form.errors.course_id }}
                </small>
              </div>

              <!-- Status Field -->
              <div class="flex flex-col">
                <label
                    for="status"
                    class="mb-2 font-semibold text-sm text-darker-700"
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
                    :invalid="!!form.errors.status"
                />
                <small
                    v-if="form.errors.status"
                    class="text-red-600 mt-1 block"
                >
                  {{ form.errors.status }}
                </small>
              </div>

              <!-- Title Field -->
              <div class="flex flex-col">
                <label
                    for="title"
                    class="mb-2 font-semibold text-sm text-darker-700"
                >
                  Page Title
                  <span class="text-red-500">*</span>
                </label>
                <InputText
                    id="title"
                    v-model="form.title"
                    type="text"
                    placeholder="Enter page title"
                    class="w-full"
                    :invalid="!!form.errors.title"
                />
                <small
                    v-if="form.errors.title"
                    class="text-red-600 mt-1 block"
                >
                  {{ form.errors.title }}
                </small>
              </div>

              <!-- Content Field -->
              <div class="flex flex-col">
                <label
                    for="content"
                    class="mb-2 font-semibold text-sm text-darker-700"
                >
                  Content
                  <span class="text-red-500">*</span>
                </label>
                <Editor
                    id="content"
                    v-model="form.content"
                    editor-style="height: 320px"
                    :class="{ 'p-invalid': !!form.errors.content }"
                />
                <small
                    v-if="form.errors.content"
                    class="text-red-600 mt-1 block"
                >
                  {{ form.errors.content }}
                </small>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-darker-200">
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
                  :label="is_editing ? 'Update Page' : 'Create Page'"
                  :loading="form.processing"
                  :disabled="form.processing"
                  class="px-6"
              />
            </div>
          </form>
        </template>
      </Card>

    </div>
  </AppLayout>
</template>

<style scoped>
/* Additional custom styles if needed */
</style>
