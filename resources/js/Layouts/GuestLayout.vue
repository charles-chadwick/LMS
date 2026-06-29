<script setup>
import { Link } from '@inertiajs/vue3';
import { GraduationCap } from 'lucide-vue-next';
import { Card, CardContent } from '@/components/ui/card';
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-linear-to-br from-primary-50 to-accent-50 px-4 py-8">
    <Link href="/" class="mb-6 flex items-center gap-2 text-primary-700">
      <GraduationCap class="h-10 w-10" />
      <span class="text-2xl font-bold">LMS</span>
    </Link>

    <Card class="w-full sm:max-w-md shadow-xl">
      <CardContent class="pt-6">
        <slot />
      </CardContent>
    </Card>
  </div>
</template>
