<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Check, Lock, Award, CheckCircle2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    course: { type: Object, required: true },
    pages: { type: Array, required: true },
    current_page: { type: Object, default: null },
    progress: { type: Object, required: true },
    is_complete: { type: Boolean, default: false },
    completed_at: { type: [String, null], default: null },
});

const currentPageId = computed(() => props.current_page?.id ?? null);

const goToPage = (page) => {
    if (page.is_locked || page.id === currentPageId.value) {
        return;
    }
    router.visit(route('courses.learn.page', [props.course.id, page.id]));
};

const completeAndContinue = () => {
    if (!props.current_page) {
        return;
    }
    router.post(route('courses.learn.complete', [props.course.id, props.current_page.id]));
};

const currentIsCompleted = computed(() =>
    props.pages.find((page) => page.id === currentPageId.value)?.is_completed ?? false,
);
</script>

<template>
  <AppLayout>
    <Head :title="`Learn: ${course.title}`" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">
      <div class="mb-6">
        <Link :href="route('courses.index')">
          <Button variant="outline">
            <ArrowLeft class="w-4 h-4" />
            Back to Courses
          </Button>
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-[20rem_1fr] gap-6">
        <!-- Sidebar -->
        <Card class="shadow-md h-fit">
          <CardContent class="pt-6">
            <h2 class="text-lg font-bold text-darker-900">{{ course.title }}</h2>
            <p class="font-mono text-sm text-darker-500 mb-4">{{ course.code }}</p>

            <div class="mb-2 flex items-center justify-between text-sm text-darker-600">
              <span>{{ progress.completed_count }} of {{ progress.total_count }} complete</span>
              <span class="font-semibold">{{ progress.percent }}%</span>
            </div>
            <div class="h-2 w-full rounded-full bg-darker-200 mb-6">
              <div class="h-2 rounded-full bg-primary-600 transition-all" :style="{ width: `${progress.percent}%` }" />
            </div>

            <ul class="space-y-1">
              <li v-for="page in pages" :key="page.id">
                <button
                    type="button"
                    :disabled="page.is_locked"
                    class="w-full flex items-center gap-2 rounded-md px-3 py-2 text-left text-sm transition-colors"
                    :class="[
                      page.id === currentPageId ? 'bg-primary-100 text-primary-800 font-semibold' : 'hover:bg-darker-100 text-darker-700',
                      page.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                    ]"
                    @click="goToPage(page)"
                >
                  <Check v-if="page.is_completed" class="w-4 h-4 shrink-0 text-primary-600" />
                  <Lock v-else-if="page.is_locked" class="w-4 h-4 shrink-0 text-darker-400" />
                  <span v-else class="w-4 h-4 shrink-0 rounded-full border border-darker-300" />
                  <span class="truncate">{{ page.title }}</span>
                </button>
              </li>
            </ul>
          </CardContent>
        </Card>

        <!-- Main panel -->
        <div>
          <Card v-if="is_complete" class="shadow-lg">
            <CardContent class="pt-6 text-center py-16">
              <CheckCircle2 class="w-16 h-16 text-primary-600 mx-auto mb-4" />
              <h1 class="text-3xl font-bold text-darker-900 mb-2">Course complete!</h1>
              <p class="text-darker-600 mb-8">You have completed every page of this course.</p>
              <Link :href="route('courses.certificate', course.id)">
                <Button class="px-6">
                  <Award class="w-4 h-4" />
                  View certificate
                </Button>
              </Link>
            </CardContent>
          </Card>

          <Card v-else-if="current_page" class="shadow-lg">
            <CardContent class="pt-6">
              <h1 class="text-3xl font-bold text-darker-900 mb-6">{{ current_page.title }}</h1>
              <div class="prose max-w-none" v-html="current_page.content"></div>

              <div class="mt-8 pt-6 border-t border-darker-200 flex justify-end">
                <Button class="px-6" @click="completeAndContinue">
                  <Check class="w-4 h-4" />
                  {{ currentIsCompleted ? 'Continue' : 'Mark complete & continue' }}
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card v-else class="shadow-lg">
            <CardContent class="pt-6 text-center py-16">
              <p class="text-darker-500 text-lg">This course has no published pages yet.</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
