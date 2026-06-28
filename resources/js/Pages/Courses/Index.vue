<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, Search, Inbox, Eye, Pencil, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import ConfirmAction from '@/components/ConfirmAction.vue';
import Pagination from '@/components/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    courses: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    status_options: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const canCreateCourses = computed(() => page.props.auth?.can?.create_courses ?? false);

const search = ref(props.filters.search || '');
const status_filter = ref(props.filters.status || 'all');

const status_filter_options = [
    { label: 'All Statuses', value: 'all' },
    ...props.status_options,
];

watch([search, status_filter], () => {
    router.get(
        route('courses.index'),
        {
            search: search.value,
            status: status_filter.value === 'all' ? '' : status_filter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
});

const getStatusVariant = (status) => {
    const variants = {
        Published: 'default',
        Draft: 'secondary',
        Archived: 'outline',
    };
    return variants[status] || 'secondary';
};

const viewCourse = (courseId) => {
    router.visit(route('courses.show', courseId));
};

const editCourse = (courseId) => {
    router.visit(route('courses.edit', courseId));
};

const deleteCourse = (course) => {
    router.delete(route('courses.destroy', course.id), { preserveScroll: true });
};

const createCourse = () => {
    router.visit(route('courses.create'));
};
</script>

<template>
  <AppLayout>
    <Head title="Courses" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-darker-900">Courses</h1>
            <p class="mt-2 text-sm text-darker-600">Manage and organize your courses</p>
          </div>
          <Button v-if="canCreateCourses" class="px-6" @click="createCourse">
            <Plus class="w-4 h-4" />
            Create Course
          </Button>
        </div>
      </div>

      <!-- Filters -->
      <Card class="mb-6 shadow-md">
        <CardContent class="pt-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label for="search" class="mb-2">Search</Label>
              <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-darker-400" />
                <Input
                    id="search"
                    v-model="search"
                    placeholder="Search by title or code..."
                    class="w-full pl-9"
                />
              </div>
            </div>

            <div>
              <Label for="status" class="mb-2">Status</Label>
              <Select id="status" v-model="status_filter">
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Filter by status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                      v-for="option in status_filter_options"
                      :key="option.value"
                      :value="option.value"
                  >
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Table -->
      <Card class="shadow-lg">
        <CardContent class="pt-6">
          <div v-if="courses.data.length === 0" class="text-center py-12">
            <Inbox class="w-16 h-16 text-darker-300 mb-4 mx-auto" />
            <p class="text-darker-500 text-lg mb-2">No courses found</p>
            <p class="text-darker-400 text-sm mb-6">
              {{ search || status_filter !== 'all' ? 'Try adjusting your filters' : 'Get started by creating your first course' }}
            </p>
            <Button v-if="!search && status_filter === 'all' && canCreateCourses" @click="createCourse">
              <Plus class="w-4 h-4" />
              Create Your First Course
            </Button>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-darker-200">
                  <th class="text-left py-4 px-4 font-semibold text-sm text-darker-700">Code</th>
                  <th class="text-left py-4 px-4 font-semibold text-sm text-darker-700">Title</th>
                  <th class="text-left py-4 px-4 font-semibold text-sm text-darker-700">Status</th>
                  <th class="text-center py-4 px-4 font-semibold text-sm text-darker-700">Pages</th>
                  <th class="text-center py-4 px-4 font-semibold text-sm text-darker-700">Students</th>
                  <th class="text-center py-4 px-4 font-semibold text-sm text-darker-700">Instructors</th>
                  <th class="text-right py-4 px-4 font-semibold text-sm text-darker-700">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                    v-for="course in courses.data"
                    :key="course.id"
                    class="border-b border-darker-100 hover:bg-darker-50 transition-colors"
                >
                  <td class="py-4 px-4">
                    <span class="font-mono text-sm text-darker-700 font-semibold">{{ course.code }}</span>
                  </td>
                  <td class="py-4 px-4">
                    <Link
                        :href="route('courses.show', course.id)"
                        class="text-primary-600 hover:text-primary-800 font-medium hover:underline"
                    >
                      {{ course.title }}
                    </Link>
                  </td>
                  <td class="py-4 px-4">
                    <Badge :variant="getStatusVariant(course.status)">{{ course.status }}</Badge>
                  </td>
                  <td class="py-4 px-4 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-700 text-sm font-semibold">
                      {{ course.pages_count }}
                    </span>
                  </td>
                  <td class="py-4 px-4 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-accent-100 text-accent-700 text-sm font-semibold">
                      {{ course.students_count }}
                    </span>
                  </td>
                  <td class="py-4 px-4 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-darker-200 text-darker-700 text-sm font-semibold">
                      {{ course.instructors_count }}
                    </span>
                  </td>
                  <td class="py-4 px-4">
                    <div class="flex items-center justify-end gap-2">
                      <Button variant="outline" size="icon-sm" aria-label="View" title="View" @click="viewCourse(course.id)">
                        <Eye class="w-4 h-4" />
                      </Button>
                      <Button v-if="course.can_update" variant="outline" size="icon-sm" aria-label="Edit" title="Edit" @click="editCourse(course.id)">
                        <Pencil class="w-4 h-4" />
                      </Button>
                      <ConfirmAction
                          v-if="course.can_update"
                          title="Delete course?"
                          :description="`Are you sure you want to delete &quot;${course.title}&quot;?`"
                          confirm-label="Delete"
                          @confirm="deleteCourse(course)"
                      >
                        <Button variant="outline" size="icon-sm" class="text-destructive border-destructive hover:bg-destructive/10" aria-label="Delete" title="Delete">
                          <Trash2 class="w-4 h-4" />
                        </Button>
                      </ConfirmAction>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="courses.data.length > 0" class="mt-6 pt-6 border-t border-darker-200">
            <Pagination :pagination="courses" />
          </div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
