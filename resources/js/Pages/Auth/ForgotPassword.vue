<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
  <GuestLayout>
    <Head title="Forgot Password" />

    <h1 class="text-xl font-bold text-darker-900 mb-4">Forgot password</h1>

    <div class="mb-4 text-sm text-darker-600">
      Forgot your password? No problem. Enter your email address and we will
      email you a password reset link to choose a new one.
    </div>

    <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
      {{ status }}
    </div>

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

      <div class="flex items-center justify-end">
        <Button type="submit" :disabled="form.processing">Email Password Reset Link</Button>
      </div>
    </form>
  </GuestLayout>
</template>
