<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
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
        required: true,
    },
    discussion: {
        type: Object,
        default: null,
    },
    types: {
        type: Array,
        required: true,
    },
    can: {
        type: Object,
        default: () => ({ create_announcement: false }),
    },
});

const is_editing = computed(() => props.discussion !== null);

const type_options = computed(() =>
    props.types.filter((option) => option.value !== 'Announcement' || props.can.create_announcement),
);

const form = useForm({
    type: props.discussion?.type || 'General',
    title: props.discussion?.title || '',
    body: '',
});

const submit = () => {
    if (is_editing.value) {
        form.put(route('courses.discussions.update', [props.course.id, props.discussion.id]), {
            preserveScroll: true,
        });
    } else {
        form.post(route('courses.discussions.store', props.course.id), { preserveScroll: true });
    }
};

const cancel = () => {
    if (is_editing.value) {
        window.location.href = route('courses.discussions.show', [props.course.id, props.discussion.id]);
    } else {
        window.location.href = route('courses.discussions.index', props.course.id);
    }
};
</script>

<template>
  <AppLayout>
    <Head :title="is_editing ? 'Edit Discussion' : 'Start Discussion'" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <div class="mb-6">
        <h1 class="text-3xl font-bold text-darker-900">
          {{ is_editing ? 'Edit Discussion' : 'Start a Discussion' }}
        </h1>
        <p class="mt-2 text-sm text-darker-600">
          {{ course.title }} <span class="font-mono">({{ course.code }})</span>
        </p>
      </div>

      <Card class="shadow-lg">
        <CardContent class="pt-6">
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Type -->
            <div class="flex flex-col">
              <Label for="type" class="mb-2">Type <span class="text-red-500">*</span></Label>
              <Select id="type" v-model="form.type">
                <SelectTrigger class="w-full" :class="{ 'border-red-500': form.errors.type }">
                  <SelectValue placeholder="Select a type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                      v-for="option in type_options"
                      :key="option.value"
                      :value="option.value"
                  >
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <small v-if="form.errors.type" class="text-red-600 mt-1 block">{{ form.errors.type }}</small>
            </div>

            <!-- Title -->
            <div class="flex flex-col">
              <Label for="title" class="mb-2">Title <span class="text-red-500">*</span></Label>
              <Input
                  id="title"
                  v-model="form.title"
                  type="text"
                  placeholder="Enter a clear, specific title"
                  :class="{ 'border-red-500': form.errors.title }"
              />
              <small v-if="form.errors.title" class="text-red-600 mt-1 block">{{ form.errors.title }}</small>
            </div>

            <!-- Opening post (create only) -->
            <div v-if="!is_editing" class="flex flex-col">
              <Label for="body" class="mb-2">Opening Post <span class="text-red-500">*</span></Label>
              <QuillEditor v-model="form.body" placeholder="Write the first post to get the discussion going..." />
              <small v-if="form.errors.body" class="text-red-600 mt-1 block">{{ form.errors.body }}</small>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-darker-200">
              <Button type="button" variant="outline" :disabled="form.processing" class="px-6" @click="cancel">
                Cancel
              </Button>
              <Button type="submit" :disabled="form.processing" class="px-6">
                {{ is_editing ? 'Update Discussion' : 'Start Discussion' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
