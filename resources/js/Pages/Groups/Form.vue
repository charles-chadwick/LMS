<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    group: {
        type: Object,
        default: null,
    },
    type_options: {
        type: Array,
        required: true,
    },
});

const is_editing = computed(() => props.group !== null);

const form = useForm({
    type: props.group?.type || 'Student',
    name: props.group?.name || '',
    description: props.group?.description || '',
});

const submit = () => {
    if (is_editing.value) {
        form.put(route('groups.update', props.group.id), { preserveScroll: true });
    } else {
        form.post(route('groups.store'), { preserveScroll: true });
    }
};

const cancel = () => {
    if (is_editing.value) {
        window.history.back();
    } else {
        window.location.href = route('groups.index');
    }
};
</script>

<template>
  <AppLayout>
    <Head :title="is_editing ? 'Edit Group' : 'Create Group'" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <div class="mb-6">
        <h1 class="text-3xl font-bold text-darker-900">
          {{ is_editing ? 'Edit Group' : 'Create New Group' }}
        </h1>
        <p class="mt-2 text-sm text-darker-600">
          {{ is_editing ? 'Update group information' : 'Fill in the details to create a new group' }}
        </p>
      </div>

      <Card class="shadow-lg">
        <CardContent class="pt-6">
          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
              <!-- Name Field -->
              <div class="flex flex-col sm:col-span-2">
                <Label for="name" class="mb-2">
                  Name <span class="text-red-500">*</span>
                </Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    placeholder="Enter group name"
                    :class="{ 'border-red-500': form.errors.name }"
                />
                <small v-if="form.errors.name" class="text-red-600 mt-1 block">
                  {{ form.errors.name }}
                </small>
              </div>

              <!-- Type Field -->
              <div class="flex flex-col">
                <Label for="type" class="mb-2">
                  Type <span class="text-red-500">*</span>
                </Label>
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
                <small v-if="form.errors.type" class="text-red-600 mt-1 block">
                  {{ form.errors.type }}
                </small>
              </div>
            </div>

            <!-- Description Field -->
            <div class="flex flex-col">
              <Label for="description" class="mb-2">
                Description <span class="text-red-500">*</span>
              </Label>
              <Textarea
                  id="description"
                  v-model="form.description"
                  placeholder="Describe the purpose of this group"
                  rows="4"
                  :class="{ 'border-red-500': form.errors.description }"
              />
              <small v-if="form.errors.description" class="text-red-600 mt-1 block">
                {{ form.errors.description }}
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
                {{ is_editing ? 'Update Group' : 'Create Group' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
