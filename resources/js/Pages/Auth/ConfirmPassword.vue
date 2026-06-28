<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
  <GuestLayout>
    <Head title="Confirm Password" />

    <h1 class="text-xl font-bold text-darker-900 mb-4">Confirm password</h1>

    <div class="mb-4 text-sm text-darker-600">
      This is a secure area of the application. Please confirm your password
      before continuing.
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <div class="flex flex-col">
        <Label for="password" class="mb-2">Password</Label>
        <Input
            id="password"
            v-model="form.password"
            type="password"
            required
            autocomplete="current-password"
            autofocus
            :class="{ 'border-red-500': form.errors.password }"
        />
        <small v-if="form.errors.password" class="text-red-600 mt-1">{{ form.errors.password }}</small>
      </div>

      <div class="flex justify-end">
        <Button type="submit" :disabled="form.processing">Confirm</Button>
      </div>
    </form>
  </GuestLayout>
</template>
