<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
  <GuestLayout>
    <Head title="Reset Password" />

    <h1 class="text-xl font-bold text-darker-900 mb-6">Reset password</h1>

    <form class="space-y-4" @submit.prevent="submit">
      <div class="flex flex-col">
        <Label for="email" class="mb-2">Email</Label>
        <Input
            id="email"
            v-model="form.email"
            type="email"
            required
            autofocus
            autocomplete="username"
            :class="{ 'border-red-500': form.errors.email }"
        />
        <small v-if="form.errors.email" class="text-red-600 mt-1">{{ form.errors.email }}</small>
      </div>

      <div class="flex flex-col">
        <Label for="password" class="mb-2">Password</Label>
        <Input
            id="password"
            v-model="form.password"
            type="password"
            required
            autocomplete="new-password"
            :class="{ 'border-red-500': form.errors.password }"
        />
        <small v-if="form.errors.password" class="text-red-600 mt-1">{{ form.errors.password }}</small>
      </div>

      <div class="flex flex-col">
        <Label for="password_confirmation" class="mb-2">Confirm Password</Label>
        <Input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            :class="{ 'border-red-500': form.errors.password_confirmation }"
        />
        <small v-if="form.errors.password_confirmation" class="text-red-600 mt-1">
          {{ form.errors.password_confirmation }}
        </small>
      </div>

      <div class="flex items-center justify-end">
        <Button type="submit" :disabled="form.processing">Reset Password</Button>
      </div>
    </form>
  </GuestLayout>
</template>
