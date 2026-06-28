<script setup>
import { router, Head } from '@inertiajs/vue3';
import {
    ArrowLeft, Pencil, Trash2, Tag as TagIcon, Users, User, FileText,
    Plus, ChevronUp, ChevronDown, Info, AlignLeft,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import ConfirmAction from '@/components/ConfirmAction.vue';
import UserList from '@/Components/UserList.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    course: {
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

const editCourse = () => {
    router.visit(route('courses.edit', props.course.id));
};

const deleteCourse = () => {
    router.delete(route('courses.destroy', props.course.id));
};

const goToIndex = () => {
    router.visit(route('courses.index'));
};

const addPage = () => {
    router.visit(route('pages.create', { course_id: props.course.id }));
};

const viewPage = (page) => {
    router.visit(route('pages.show', page.id));
};

const editPage = (page) => {
    router.visit(route('pages.edit', page.id));
};

const deletePage = (page) => {
    router.delete(route('pages.destroy', page.id), { preserveScroll: true });
};

const persistPageOrder = (ordered_pages) => {
    router.put(
        route('pages.reorder', props.course.id),
        { pages: ordered_pages.map((page) => page.id) },
        { preserveScroll: true },
    );
};

const movePage = (index, direction) => {
    const target_index = index + direction;
    if (target_index < 0 || target_index >= props.course.pages.length) {
        return;
    }
    const ordered_pages = [...props.course.pages];
    [ordered_pages[index], ordered_pages[target_index]] =
        [ordered_pages[target_index], ordered_pages[index]];
    persistPageOrder(ordered_pages);
};
</script>

<template>
  <AppLayout>
    <Head :title="course.title" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <!-- Back Button -->
      <div class="mb-6">
        <Button variant="outline" @click="goToIndex">
          <ArrowLeft class="w-4 h-4" />
          Back to Courses
        </Button>
      </div>

      <!-- Course Header -->
      <Card class="mb-6 shadow-lg">
        <CardContent class="pt-6">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-3">
                <h1 class="text-4xl font-bold text-darker-900">
                  {{ course.title }}
                </h1>
                <Badge :variant="getStatusVariant(course.status)">
                  {{ course.status }}
                </Badge>
              </div>
              <div class="flex items-center gap-2 text-darker-600 mb-4">
                <TagIcon class="w-4 h-4" />
                <span class="font-mono font-semibold text-lg">
                  {{ course.code }}
                </span>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <Button variant="secondary" @click="editCourse">
                <Pencil class="w-4 h-4" />
                Edit
              </Button>
              <ConfirmAction
                  title="Delete course?"
                  :description="`Are you sure you want to delete &quot;${course.title}&quot;?`"
                  confirm-label="Delete"
                  @confirm="deleteCourse"
              >
                <Button variant="outline" class="text-destructive border-destructive hover:bg-destructive/10">
                  <Trash2 class="w-4 h-4" />
                  Delete
                </Button>
              </ConfirmAction>
            </div>
          </div>

          <!-- Statistics -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 pt-6 border-t border-darker-200">
            <div class="bg-primary-50 rounded-lg p-4 border border-primary-200">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-primary-600 font-semibold mb-1">Pages</p>
                  <p class="text-3xl font-bold text-primary-900">{{ course.pages_count }}</p>
                </div>
                <FileText class="w-8 h-8 text-primary-400" />
              </div>
            </div>

            <div class="bg-accent-50 rounded-lg p-4 border border-accent-200">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-accent-700 font-semibold mb-1">Students</p>
                  <p class="text-3xl font-bold text-accent-900">{{ course.students_count }}</p>
                </div>
                <Users class="w-8 h-8 text-accent-400" />
              </div>
            </div>

            <div class="bg-darker-100 rounded-lg p-4 border border-darker-300">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-darker-600 font-semibold mb-1">Instructors</p>
                  <p class="text-3xl font-bold text-darker-900">{{ course.instructors_count }}</p>
                </div>
                <User class="w-8 h-8 text-darker-400" />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Description -->
      <Card v-if="course.description" class="mb-6 shadow-md">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-lg">
            <AlignLeft class="w-5 h-5 text-primary-600" />
            Description
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="prose max-w-none" v-html="course.description"></div>
        </CardContent>
      </Card>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Instructors -->
        <Card class="shadow-md">
          <CardHeader>
            <CardTitle class="flex items-center gap-2 text-lg">
              <User class="w-5 h-5 text-primary-600" />
              Instructors
            </CardTitle>
          </CardHeader>
          <CardContent>
            <UserList v-if="course.instructors && course.instructors.length > 0" :users="course.instructors" />
            <div v-else class="text-center py-8 text-darker-500">
              <Users class="w-10 h-10 mb-3 mx-auto" />
              <p>No instructors assigned yet</p>
            </div>
          </CardContent>
        </Card>

        <!-- Students -->
        <Card class="shadow-md">
          <CardHeader>
            <CardTitle class="flex items-center gap-2 text-lg">
              <Users class="w-5 h-5 text-accent-600" />
              Students
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="course.students && course.students.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
              <div
                  v-for="student in course.students"
                  :key="student.id"
                  class="flex items-center gap-3 p-3 bg-darker-50 rounded-lg"
              >
                <div class="w-10 h-10 rounded-full bg-accent-200 flex items-center justify-center">
                  <User class="w-5 h-5 text-accent-700" />
                </div>
                <div>
                  <p class="font-semibold text-darker-900">
                    {{ student.first_name }} {{ student.last_name }}
                  </p>
                  <p class="text-sm text-darker-600">{{ student.email }}</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-darker-500">
              <Users class="w-10 h-10 mb-3 mx-auto" />
              <p>No students enrolled yet</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Pages -->
      <Card class="mt-6 shadow-md">
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle class="flex items-center gap-2 text-lg">
              <FileText class="w-5 h-5 text-primary-600" />
              Course Pages
            </CardTitle>
            <Button size="sm" @click="addPage">
              <Plus class="w-4 h-4" />
              Add Page
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <div v-if="course.pages && course.pages.length > 0" class="space-y-3">
            <div
                v-for="(page, index) in course.pages"
                :key="page.id"
                class="flex items-center justify-between p-4 bg-darker-50 rounded-lg hover:bg-darker-100 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="flex flex-col">
                  <button
                      type="button"
                      class="text-darker-400 hover:text-primary-600 disabled:opacity-30"
                      :disabled="index === 0"
                      aria-label="Move page up"
                      @click="movePage(index, -1)"
                  >
                    <ChevronUp class="w-4 h-4" />
                  </button>
                  <button
                      type="button"
                      class="text-darker-400 hover:text-primary-600 disabled:opacity-30"
                      :disabled="index === course.pages.length - 1"
                      aria-label="Move page down"
                      @click="movePage(index, 1)"
                  >
                    <ChevronDown class="w-4 h-4" />
                  </button>
                </div>
                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-200 text-primary-700 font-semibold text-sm">
                  {{ page.order }}
                </span>
                <div>
                  <button
                      type="button"
                      class="font-semibold text-darker-900 hover:text-primary-600 text-left"
                      @click="viewPage(page)"
                  >
                    {{ page.title }}
                  </button>
                  <p class="text-sm text-darker-600">Status: {{ page.status }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Badge :variant="getStatusVariant(page.status)">{{ page.status }}</Badge>
                <Button variant="ghost" size="icon-sm" aria-label="Edit page" @click="editPage(page)">
                  <Pencil class="w-4 h-4" />
                </Button>
                <ConfirmAction
                    title="Delete page?"
                    :description="`Are you sure you want to delete &quot;${page.title}&quot;?`"
                    confirm-label="Delete"
                    @confirm="deletePage(page)"
                >
                  <Button variant="ghost" size="icon-sm" class="text-destructive hover:bg-destructive/10" aria-label="Delete page">
                    <Trash2 class="w-4 h-4" />
                  </Button>
                </ConfirmAction>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-darker-500">
            <FileText class="w-10 h-10 mb-3 mx-auto" />
            <p class="mb-4">No pages created yet</p>
            <Button @click="addPage">
              <Plus class="w-4 h-4" />
              Add the first page
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Metadata -->
      <Card class="mt-6 shadow-md">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-lg">
            <Info class="w-5 h-5 text-darker-600" />
            Metadata
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-darker-600 mb-1">Created</p>
              <p class="font-semibold text-darker-900">
                {{ course.created_at }}
                <span v-if="course.created_by" class="text-darker-600 font-normal">
                  by {{ course.created_by.first_name }} {{ course.created_by.last_name }}
                </span>
              </p>
            </div>
            <div>
              <p class="text-sm text-darker-600 mb-1">Last Updated</p>
              <p class="font-semibold text-darker-900">
                {{ course.updated_at }}
                <span v-if="course.updated_by" class="text-darker-600 font-normal">
                  by {{ course.updated_by.first_name }} {{ course.updated_by.last_name }}
                </span>
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
