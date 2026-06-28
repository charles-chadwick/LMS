<script setup>
import { router, Head } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Trash2, BookOpen } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import ConfirmAction from '@/components/ConfirmAction.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    page: {
        type: Object,
        required: true,
    },
});

const getStatusVariant = (status) => {
    const variants = {
        Published: 'default',
        Draft: 'secondary',
        Archived: 'outline',
    };
    return variants[status] || 'secondary';
};

const editPage = () => {
    router.visit(route('pages.edit', props.page.id));
};

const backToCourse = () => {
    router.visit(route('courses.show', props.page.course_id));
};

const deletePage = () => {
    router.delete(route('pages.destroy', props.page.id));
};
</script>

<template>
  <AppLayout>
    <Head :title="page.title" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <!-- Back Button -->
      <div class="mb-6">
        <Button variant="outline" @click="backToCourse">
          <ArrowLeft class="w-4 h-4" />
          Back to Course
        </Button>
      </div>

      <!-- Page Header -->
      <Card class="mb-6 shadow-lg">
        <CardContent class="pt-6">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-3">
                <h1 class="text-4xl font-bold text-darker-900">
                  {{ page.title }}
                </h1>
                <Badge :variant="getStatusVariant(page.status)">{{ page.status }}</Badge>
              </div>
              <div v-if="page.course" class="flex items-center gap-2 text-darker-600">
                <BookOpen class="w-4 h-4" />
                <span class="font-semibold">{{ page.course.title }}</span>
                <span class="font-mono text-darker-500">({{ page.course.code }})</span>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <Button variant="secondary" @click="editPage">
                <Pencil class="w-4 h-4" />
                Edit
              </Button>
              <ConfirmAction
                  title="Delete page?"
                  :description="`Are you sure you want to delete &quot;${page.title}&quot;?`"
                  confirm-label="Delete"
                  @confirm="deletePage"
              >
                <Button variant="outline" class="text-destructive border-destructive hover:bg-destructive/10">
                  <Trash2 class="w-4 h-4" />
                  Delete
                </Button>
              </ConfirmAction>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Page Content -->
      <Card class="shadow-md">
        <CardContent class="pt-6">
          <div class="prose max-w-none" v-html="page.content"></div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
