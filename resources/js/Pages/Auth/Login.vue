<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
  <GuestLayout>
    <Head title="Log in" />

    <h1 class="text-xl font-bold text-darker-900 mb-6">Log in</h1>

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

      <div class="flex flex-col">
        <Label for="password" class="mb-2">Password</Label>
        <Input
            id="password"
            v-model="form.password"
            type="password"
            required
            autocomplete="current-password"
            :class="{ 'border-red-500': form.errors.password }"
        />
        <small v-if="form.errors.password" class="text-red-600 mt-1">{{ form.errors.password }}</small>
      </div>

      <label class="flex items-center gap-2">
        <Checkbox v-model="form.remember" />
        <span class="text-sm text-darker-600">Remember me</span>
      </label>

      <div class="flex items-center justify-end gap-4">
        <Link
            v-if="canResetPassword"
            :href="route('password.request')"
            class="text-sm text-darker-600 underline hover:text-darker-900"
        >
          Forgot your password?
        </Link>
        <Button type="submit" :disabled="form.processing">Log in</Button>
      </div>
    </form>
  </GuestLayout>
</template>
