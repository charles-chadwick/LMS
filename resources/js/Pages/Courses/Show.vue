<script setup>
import { computed, ref, watch } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import {
    ArrowLeft, Pencil, Trash2, Tag as TagIcon, Users, User, FileText,
    Plus, ChevronUp, ChevronDown, Info, AlignLeft, X, UserPlus, GripVertical,
    MessagesSquare,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import ConfirmAction from '@/components/ConfirmAction.vue';
import Avatar from '@/components/Avatar.vue';
import UserSearchSelect from '@/components/UserSearchSelect.vue';
import GroupSearchSelect from '@/components/GroupSearchSelect.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import draggable from 'vuedraggable';
import { fromNow } from "@/lib/date.js";

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
    can: {
        type: Object,
        default: () => ({ update: false, manage_instructors: false, manage_students: false }),
    },
});

const canManage = computed(() => props.can.update);

const canManageInstructors = computed(() => props.can.manage_instructors);

const selected_instructor_id = ref('');

const addInstructor = () => {
    if (!selected_instructor_id.value) {
        return;
    }
    router.post(
        route('courses.instructors.store', props.course.id),
        { user_id: selected_instructor_id.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selected_instructor_id.value = '';
            },
        },
    );
};

const removeInstructor = (instructor) => {
    router.delete(
        route('courses.instructors.destroy', { course: props.course.id, user: instructor.id }),
        { preserveScroll: true },
    );
};

const canManageStudents = computed(() => props.can.manage_students);

const selected_student_id = ref('');

const addStudent = () => {
    if (!selected_student_id.value) {
        return;
    }
    router.post(
        route('courses.students.store', props.course.id),
        { user_id: selected_student_id.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selected_student_id.value = '';
            },
        },
    );
};

const selected_group_id = ref('');

const addGroup = () => {
    if (!selected_group_id.value) {
        return;
    }
    router.post(
        route('courses.students.storeGroup', props.course.id),
        { group_id: selected_group_id.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selected_group_id.value = '';
            },
        },
    );
};

const removeStudent = (student) => {
    router.delete(
        route('courses.students.destroy', { course: props.course.id, user: student.id }),
        { preserveScroll: true },
    );
};

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

const goToDiscussions = () => {
    router.visit(route('courses.discussions.index', props.course.id));
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

const ordered_pages = ref([...(props.course.pages ?? [])]);

watch(
    () => props.course.pages,
    (pages) => {
        ordered_pages.value = [...(pages ?? [])];
    },
);

const onDragEnd = () => {
    persistPageOrder(ordered_pages.value);
};

const persistPageOrder = (pages_in_order) => {
    const new_order = pages_in_order.map((page) => page.id);
    const current_order = (props.course.pages ?? []).map((page) => page.id);
    if (new_order.join(',') === current_order.join(',')) {
        return;
    }
    router.put(
        route('pages.reorder', props.course.id),
        { pages: new_order },
        { preserveScroll: true },
    );
};

const movePage = (index, direction) => {
    const target_index = index + direction;
    if (target_index < 0 || target_index >= ordered_pages.value.length) {
        return;
    }
    const reordered = [...ordered_pages.value];
    [reordered[index], reordered[target_index]] =
        [reordered[target_index], reordered[index]];
    ordered_pages.value = reordered;
    persistPageOrder(reordered);
};
</script>

<template>
  <AppLayout>
    <Head :title="course.title" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <!-- Back Button -->
      <div class="mb-6 flex items-center justify-between">
        <Button variant="outline" @click="goToIndex">
          <ArrowLeft class="w-4 h-4" />
          Back to Courses
        </Button>
        <Button variant="outline" @click="goToDiscussions">
          <MessagesSquare class="w-4 h-4" />
          Discussions
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

            <div v-if="canManage" class="flex items-center gap-3">
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
            <div v-if="course.instructors && course.instructors.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
              <div
                  v-for="instructor in course.instructors"
                  :key="instructor.id"
                  class="flex items-center justify-between gap-3 p-3 bg-darker-50 rounded-lg"
              >
                <div class="flex items-center gap-3">
                  <Avatar :user="instructor" variant="primary" />
                  <div>
                    <p class="font-semibold text-darker-900">
                      {{ instructor.first_name }} {{ instructor.last_name }}
                    </p>
                    <p class="text-sm text-darker-600">{{ instructor.email }}</p>
                  </div>
                </div>
                <Button
                    v-if="canManageInstructors"
                    variant="ghost"
                    size="icon-sm"
                    class="text-destructive hover:bg-destructive/10 disabled:opacity-30"
                    :disabled="course.instructors.length === 1"
                    :aria-label="`Remove ${instructor.first_name} ${instructor.last_name}`"
                    @click="removeInstructor(instructor)"
                >
                  <X class="w-4 h-4" />
                </Button>
              </div>
            </div>
            <div v-else class="text-center py-8 text-darker-500">
              <Users class="w-10 h-10 mb-3 mx-auto" />
              <p>No instructors assigned yet</p>
            </div>

            <!-- Add instructor -->
            <div v-if="canManageInstructors" class="mt-4 pt-4 border-t border-darker-200 flex items-center gap-2">
              <div class="flex-1">
                <UserSearchSelect
                    v-model="selected_instructor_id"
                    :search-url="route('courses.instructors.assignable', course.id)"
                    variant="primary"
                    placeholder="Search for an instructor…"
                />
              </div>
              <Button :disabled="!selected_instructor_id" @click="addInstructor">
                <UserPlus class="w-4 h-4" />
                Add
              </Button>
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
                  class="flex items-center justify-between gap-3 p-3 bg-darker-50 rounded-lg"
              >
                <div class="flex items-center gap-3">
                  <Avatar :user="student" variant="accent" />
                  <div>
                    <p class="font-semibold text-darker-900">
                      {{ student.first_name }} {{ student.last_name }}
                    </p>
                    <p class="text-sm text-darker-600">{{ student.email }}</p>
                  </div>
                </div>
                <Button
                    v-if="canManageStudents"
                    variant="ghost"
                    size="icon-sm"
                    class="text-destructive hover:bg-destructive/10"
                    :aria-label="`Remove ${student.first_name} ${student.last_name}`"
                    @click="removeStudent(student)"
                >
                  <X class="w-4 h-4" />
                </Button>
              </div>
            </div>
            <div v-else class="text-center py-8 text-darker-500">
              <Users class="w-10 h-10 mb-3 mx-auto" />
              <p>No students enrolled yet</p>
            </div>

            <!-- Add student -->
            <div v-if="canManageStudents" class="mt-4 pt-4 border-t border-darker-200 flex items-center gap-2">
              <div class="flex-1">
                <UserSearchSelect
                    v-model="selected_student_id"
                    :search-url="route('courses.students.assignable', course.id)"
                    variant="accent"
                    placeholder="Search for a student…"
                />
              </div>
              <Button :disabled="!selected_student_id" @click="addStudent">
                <UserPlus class="w-4 h-4" />
                Add
              </Button>
            </div>

            <!-- Add a group's members -->
            <div v-if="canManageStudents" class="mt-2 flex items-center gap-2">
              <div class="flex-1">
                <GroupSearchSelect
                    v-model="selected_group_id"
                    :search-url="route('courses.students.assignable-groups', course.id)"
                    placeholder="Search for a group…"
                />
              </div>
              <Button :disabled="!selected_group_id" @click="addGroup">
                <Users class="w-4 h-4" />
                Add group
              </Button>
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
            <Button v-if="canManage" size="sm" @click="addPage">
              <Plus class="w-4 h-4" />
              Add Page
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <draggable
              v-if="ordered_pages.length > 0"
              v-model="ordered_pages"
              item-key="id"
              handle=".page-drag-handle"
              :disabled="!canManage"
              class="space-y-3"
              @end="onDragEnd"
          >
            <template #item="{ element: page, index }">
              <div
                  class="flex items-center justify-between p-4 bg-darker-50 rounded-lg hover:bg-darker-100 transition-colors"
              >
                <div class="flex items-center gap-3">
                  <button
                      v-if="canManage"
                      type="button"
                      class="page-drag-handle cursor-grab active:cursor-grabbing text-darker-400 hover:text-primary-600"
                      aria-label="Drag to reorder page"
                  >
                    <GripVertical class="w-4 h-4" />
                  </button>
                  <div v-if="canManage" class="flex flex-col">
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
                        :disabled="index === ordered_pages.length - 1"
                        aria-label="Move page down"
                        @click="movePage(index, 1)"
                    >
                      <ChevronDown class="w-4 h-4" />
                    </button>
                  </div>
                  <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-200 text-primary-700 font-semibold text-sm">
                    {{ index + 1 }}
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
                  <template v-if="canManage">
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
                  </template>
                </div>
              </div>
            </template>
          </draggable>
          <div v-else class="text-center py-8 text-darker-500">
            <FileText class="w-10 h-10 mb-3 mx-auto" />
            <p class="mb-4">No pages created yet</p>
            <Button v-if="canManage" @click="addPage">
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
                {{fromNow( course.created_at )}}
                <span v-if="course.created_by" class="text-darker-600 font-normal">
                  by {{ course.created_by.first_name }} {{ course.created_by.last_name }}
                </span>
              </p>
            </div>
            <div>
              <p class="text-sm text-darker-600 mb-1">Last Updated</p>
              <p class="font-semibold text-darker-900">
                {{ fromNow(course.updated_at)  }}
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
