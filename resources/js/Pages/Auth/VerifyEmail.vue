<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/components/ui/button';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
  <GuestLayout>
    <Head title="Email Verification" />

    <h1 class="text-xl font-bold text-darker-900 mb-4">Verify your email</h1>

    <div class="mb-4 text-sm text-darker-600">
      Thanks for signing up! Before getting started, could you verify your email
      address by clicking on the link we just emailed to you? If you didn't
      receive the email, we will gladly send you another.
    </div>

    <div v-if="verificationLinkSent" class="mb-4 text-sm font-medium text-green-600">
      A new verification link has been sent to the email address you provided
      during registration.
    </div>

    <form @submit.prevent="submit">
      <div class="mt-4 flex items-center justify-between">
        <Button type="submit" :disabled="form.processing">Resend Verification Email</Button>
        <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="text-sm text-darker-600 underline hover:text-darker-900"
        >
          Log Out
        </Link>
      </div>
    </form>
  </GuestLayout>
</template>
