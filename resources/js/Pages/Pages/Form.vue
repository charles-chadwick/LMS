<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import QuillEditor from '@/components/QuillEditor.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

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

const preselected_course_id =
    Number(new URLSearchParams(window.location.search).get('course_id')) || null;

const form = useForm({
    course_id: props.page?.course_id || preselected_course_id,
    status: props.page?.status || 'Draft',
    title: props.page?.title || '',
    content: props.page?.content || '',
});

const submit = () => {
    if (is_editing.value) {
        form.put(route('pages.update', props.page.id), { preserveScroll: true });
    } else {
        form.post(route('pages.store'), { preserveScroll: true });
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
        <CardContent class="pt-6">
          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 gap-6">
              <!-- Course Field -->
              <div class="flex flex-col">
                <Label for="course_id" class="mb-2">
                  Course <span class="text-red-500">*</span>
                </Label>
                <Select id="course_id" v-model="form.course_id">
                  <SelectTrigger class="w-full" :class="{ 'border-red-500': form.errors.course_id }">
                    <SelectValue placeholder="Select a course" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                        v-for="course in courses"
                        :key="course.id"
                        :value="course.id"
                    >
                      {{ course.title }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <small v-if="form.errors.course_id" class="text-red-600 mt-1 block">
                  {{ form.errors.course_id }}
                </small>
              </div>

              <!-- Status Field -->
              <div class="flex flex-col">
                <Label for="status" class="mb-2">
                  Status <span class="text-red-500">*</span>
                </Label>
                <Select id="status" v-model="form.status">
                  <SelectTrigger class="w-full" :class="{ 'border-red-500': form.errors.status }">
                    <SelectValue placeholder="Select a status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                        v-for="option in status_options"
                        :key="option.value"
                        :value="option.value"
                    >
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <small v-if="form.errors.status" class="text-red-600 mt-1 block">
                  {{ form.errors.status }}
                </small>
              </div>

              <!-- Title Field -->
              <div class="flex flex-col">
                <Label for="title" class="mb-2">
                  Page Title <span class="text-red-500">*</span>
                </Label>
                <Input
                    id="title"
                    v-model="form.title"
                    type="text"
                    placeholder="Enter page title"
                    :class="{ 'border-red-500': form.errors.title }"
                />
                <small v-if="form.errors.title" class="text-red-600 mt-1 block">
                  {{ form.errors.title }}
                </small>
              </div>

              <!-- Content Field -->
              <div class="flex flex-col">
                <Label for="content" class="mb-2">
                  Content <span class="text-red-500">*</span>
                </Label>
                <QuillEditor v-model="form.content" placeholder="Write the page content..." />
                <small v-if="form.errors.content" class="text-red-600 mt-1 block">
                  {{ form.errors.content }}
                </small>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-darker-200">
              <Button
                  type="button"
                  variant="outline"
                  :disabled="form.processing"
                  class="px-6"
                  @click="cancel"
              >
                Cancel
              </Button>
              <Button type="submit" :disabled="form.processing" class="px-6">
                {{ is_editing ? 'Update Page' : 'Create Page' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
