<script setup>
import { Head, useForm } from '@inertiajs/vue3';
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
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    role_options: {
        type: Array,
        required: true,
    },
});

const is_editing = computed(() => props.user !== null);

const form = useForm({
    role: props.user?.role || 'Student',
    first_name: props.user?.first_name || '',
    last_name: props.user?.last_name || '',
    email: props.user?.email || '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    if (is_editing.value) {
        form.put(route('users.update', props.user.id), {
            preserveScroll: true,
            onSuccess: () => form.reset('password', 'password_confirmation'),
        });
    } else {
        form.post(route('users.store'), { preserveScroll: true });
    }
};

const cancel = () => {
    if (is_editing.value) {
        window.history.back();
    } else {
        window.location.href = route('users.index');
    }
};
</script>

<template>
  <AppLayout>
    <Head :title="is_editing ? 'Edit User' : 'Create User'" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <div class="mb-6">
        <h1 class="text-3xl font-bold text-darker-900">
          {{ is_editing ? 'Edit User' : 'Create New User' }}
        </h1>
        <p class="mt-2 text-sm text-darker-600">
          {{ is_editing ? 'Update user information' : 'Fill in the details to create a new user' }}
        </p>
      </div>

      <Card class="shadow-lg">
        <CardContent class="pt-6">
          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
              <!-- First Name Field -->
              <div class="flex flex-col">
                <Label for="first_name" class="mb-2">
                  First Name <span class="text-red-500">*</span>
                </Label>
                <Input
                    id="first_name"
                    v-model="form.first_name"
                    type="text"
                    placeholder="Enter first name"
                    :class="{ 'border-red-500': form.errors.first_name }"
                />
                <small v-if="form.errors.first_name" class="text-red-600 mt-1 block">
                  {{ form.errors.first_name }}
                </small>
              </div>

              <!-- Last Name Field -->
              <div class="flex flex-col">
                <Label for="last_name" class="mb-2">
                  Last Name <span class="text-red-500">*</span>
                </Label>
                <Input
                    id="last_name"
                    v-model="form.last_name"
                    type="text"
                    placeholder="Enter last name"
                    :class="{ 'border-red-500': form.errors.last_name }"
                />
                <small v-if="form.errors.last_name" class="text-red-600 mt-1 block">
                  {{ form.errors.last_name }}
                </small>
              </div>

              <!-- Role Field -->
              <div class="flex flex-col">
                <Label for="role" class="mb-2">
                  Role <span class="text-red-500">*</span>
                </Label>
                <Select id="role" v-model="form.role">
                  <SelectTrigger class="w-full" :class="{ 'border-red-500': form.errors.role }">
                    <SelectValue placeholder="Select a role" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                        v-for="option in role_options"
                        :key="option.value"
                        :value="option.value"
                    >
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <small v-if="form.errors.role" class="text-red-600 mt-1 block">
                  {{ form.errors.role }}
                </small>
              </div>
            </div>

            <!-- Email Field -->
            <div class="flex flex-col">
              <Label for="email" class="mb-2">
                Email <span class="text-red-500">*</span>
              </Label>
              <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  placeholder="Enter email address"
                  :class="{ 'border-red-500': form.errors.email }"
              />
              <small v-if="form.errors.email" class="text-red-600 mt-1 block">
                {{ form.errors.email }}
              </small>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <!-- Password Field -->
              <div class="flex flex-col">
                <Label for="password" class="mb-2">
                  Password
                  <span v-if="!is_editing" class="text-red-500">*</span>
                </Label>
                <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Enter password"
                    :class="{ 'border-red-500': form.errors.password }"
                />
                <small v-if="form.errors.password" class="text-red-600 mt-1 block">
                  {{ form.errors.password }}
                </small>
                <small v-else-if="is_editing" class="text-darker-500 mt-1 block">
                  Leave blank to keep the current password
                </small>
              </div>

              <!-- Password Confirmation Field -->
              <div class="flex flex-col">
                <Label for="password_confirmation" class="mb-2">
                  Confirm Password
                  <span v-if="!is_editing" class="text-red-500">*</span>
                </Label>
                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Re-enter password"
                />
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
                {{ is_editing ? 'Update User' : 'Create User' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
