<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
    description: props.course?.description || '',
    cover: null,
    remove_cover: false,
});

const cover_input = ref(null);
const new_cover_preview = ref(null);
const existing_cover_url = props.course?.cover?.full || null;

const displayed_cover = computed(() => {
    if (new_cover_preview.value) {
        return new_cover_preview.value;
    }

    if (existing_cover_url && !form.remove_cover) {
        return existing_cover_url;
    }

    return null;
});

const onCoverChange = (event) => {
    const file = event.target.files[0] || null;
    form.cover = file;
    form.remove_cover = false;
    new_cover_preview.value = file ? URL.createObjectURL(file) : null;
};

const removeCover = () => {
    form.cover = null;
    new_cover_preview.value = null;
    form.remove_cover = Boolean(existing_cover_url);

    if (cover_input.value) {
        cover_input.value.value = '';
    }
};

const submit = () => {
    if (is_editing.value) {
        form
            .transform((data) => ({ ...data, _method: 'put' }))
            .post(route('courses.update', props.course.id), { preserveScroll: true });
    } else {
        form.post(route('courses.store'), { preserveScroll: true });
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
  <AppLayout>
    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <div class="mb-6">
        <h1 class="text-3xl font-bold text-darker-900">
          {{ is_editing ? 'Edit Course' : 'Create New Course' }}
        </h1>
        <p class="mt-2 text-sm text-darker-600">
          {{ is_editing ? 'Update course information' : 'Fill in the details to create a new course' }}
        </p>
      </div>

      <Card class="shadow-lg">
        <CardContent class="pt-6">
          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
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
                  Course Title <span class="text-red-500">*</span>
                </Label>
                <Input
                    id="title"
                    v-model="form.title"
                    type="text"
                    placeholder="Enter course title"
                    :class="{ 'border-red-500': form.errors.title }"
                />
                <small v-if="form.errors.title" class="text-red-600 mt-1 block">
                  {{ form.errors.title }}
                </small>
              </div>

              <!-- Code Field -->
              <div class="flex flex-col">
                <Label for="code" class="mb-2">
                  Course Code <span class="text-red-500">*</span>
                </Label>
                <Input
                    id="code"
                    v-model="form.code"
                    type="text"
                    placeholder="Enter course code (e.g., CS101)"
                    :class="{ 'border-red-500': form.errors.code }"
                />
                <small v-if="form.errors.code" class="text-red-600 mt-1 block">
                  {{ form.errors.code }}
                </small>
                <small v-else class="text-darker-500 mt-1 block">
                  A unique identifier for this course
                </small>
              </div>
            </div>

            <!-- Description Field -->
            <div class="flex flex-col">
              <Label for="description" class="mb-2">
                Description <span class="text-darker-400 font-normal">(optional)</span>
              </Label>
              <QuillEditor v-model="form.description" placeholder="Describe this course..." />
              <small v-if="form.errors.description" class="text-red-600 mt-1 block">
                {{ form.errors.description }}
              </small>
            </div>

            <!-- Cover Image Field -->
            <div class="flex flex-col">
              <Label for="cover" class="mb-2">
                Cover Image <span class="text-darker-400 font-normal">(optional)</span>
              </Label>

              <div class="flex items-start gap-4">
                <div
                    class="flex h-32 w-56 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-darker-200 bg-darker-100"
                >
                  <img
                      v-if="displayed_cover"
                      :src="displayed_cover"
                      alt="Course cover preview"
                      class="h-full w-full object-cover"
                  />
                  <span v-else class="px-2 text-center text-xs text-darker-400">
                    No cover image
                  </span>
                </div>

                <div class="flex flex-col gap-2">
                  <input
                      id="cover"
                      ref="cover_input"
                      type="file"
                      accept="image/jpeg,image/png,image/webp"
                      class="block text-sm text-darker-600 file:mr-3 file:rounded-md file:border-0 file:bg-darker-900 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-darker-700"
                      @change="onCoverChange"
                  />
                  <Button
                      v-if="displayed_cover"
                      type="button"
                      variant="outline"
                      size="sm"
                      class="w-fit"
                      @click="removeCover"
                  >
                    Remove
                  </Button>
                  <small class="text-darker-500">
                    JPG, PNG, or WEBP up to 5 MB. Recommended 16:9.
                  </small>
                </div>
              </div>

              <small v-if="form.errors.cover" class="text-red-600 mt-1 block">
                {{ form.errors.cover }}
              </small>
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
                {{ is_editing ? 'Update Course' : 'Create Course' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
