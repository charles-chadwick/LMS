<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Printer, Award } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    course: { type: Object, required: true },
    student: { type: Object, required: true },
    completed_at: { type: [String, null], default: null },
});

const studentName = computed(() => `${props.student.first_name ?? ''} ${props.student.last_name ?? ''}`.trim());

const formattedCompletedAt = computed(() => {
    if (!props.completed_at) {
        return null;
    }
    return new Date(props.completed_at.replace(' ', 'T')).toLocaleDateString(undefined, {
        year: 'numeric', month: 'long', day: 'numeric',
    });
});

const printCertificate = () => {
    window.print();
};
</script>

<template>
  <AppLayout>
    <Head :title="`Certificate: ${course.title}`" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">
      <div class="mb-6 flex items-center justify-between print:hidden">
        <Button variant="outline" @click="router.visit(route('courses.learn', course.id))">
          <ArrowLeft class="w-4 h-4" />
          Back to Course
        </Button>
        <Button @click="printCertificate">
          <Printer class="w-4 h-4" />
          Print
        </Button>
      </div>

      <div class="mx-auto max-w-3xl bg-white border-8 border-primary-600 rounded-lg p-12 text-center shadow-lg">
        <Award class="w-16 h-16 text-primary-600 mx-auto mb-6" />
        <p class="text-sm uppercase tracking-widest text-darker-500 mb-2">Certificate of Completion</p>
        <p class="text-darker-600 mb-6">This certifies that</p>
        <h1 class="text-4xl font-bold text-darker-900 mb-6">{{ studentName }}</h1>
        <p class="text-darker-600 mb-2">has successfully completed</p>
        <h2 class="text-2xl font-semibold text-darker-900">{{ course.title }}</h2>
        <p class="font-mono text-darker-500 mb-8">{{ course.code }}</p>
        <p v-if="formattedCompletedAt" class="text-darker-600">Completed on {{ formattedCompletedAt }}</p>
      </div>
    </div>
  </AppLayout>
</template>
