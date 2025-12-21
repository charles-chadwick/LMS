<script setup>
import { router } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { Button, Card, ConfirmDialog, Tag } from 'primevue';
import UserList from '@/Components/UserList.vue';
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
});

const confirm = useConfirm();

const getStatusSeverity = (status) => {
    const severities = {
        'Published': 'success',
        'Draft': 'warn',
        'Archived': 'secondary',
    };
    return severities[status] || 'info';
};

const editCourse = () => {
    router.visit(route('courses.edit', props.course.id));
};

const confirmDelete = () => {
    confirm.require({
        message: `Are you sure you want to delete "${props.course.title}"?`,
        header: 'Confirm Deletion',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Delete',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('courses.destroy', props.course.id));
        },
    });
};

const goToIndex = () => {
    router.visit(route('courses.index'));
};
</script>

<template>
  <AppLayout>
    <Head :title="course.title" />

    <ConfirmDialog />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

            <!-- Back Button -->
            <div class="mb-6">
                <Button
                    label="Back to Courses"
                    icon="pi pi-arrow-left"
                    severity="secondary"
                    outlined
                    @click="goToIndex"
                />
            </div>

            <!-- Course Header -->
            <Card class="mb-6 shadow-lg">
                <template #content>
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <h1 class="text-4xl font-bold text-darker-900">
                                    {{ course.title }}
                                </h1>
                                <Tag
                                    :value="course.status"
                                    :severity="getStatusSeverity(course.status)"
                                    class="text-sm"
                                />
                            </div>
                            <div class="flex items-center gap-2 text-darker-600 mb-4">
                                <i class="pi pi-tag"></i>
                                <span class="font-mono font-semibold text-lg">
                                    {{ course.code }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Button
                                label="Edit"
                                icon="pi pi-pencil"
                                severity="secondary"
                                @click="editCourse"
                            />
                            <Button
                                label="Delete"
                                icon="pi pi-trash"
                                severity="danger"
                                outlined
                                @click="confirmDelete"
                            />
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 pt-6 border-t border-darker-200">
                        <div class="bg-primary-50 rounded-lg p-4 border border-primary-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-primary-600 font-semibold mb-1">
                                        Pages
                                    </p>
                                    <p class="text-3xl font-bold text-primary-900">
                                        {{ course.pages_count }}
                                    </p>
                                </div>
                                <i class="pi pi-file text-3xl text-primary-400"></i>
                            </div>
                        </div>

                        <div class="bg-accent-50 rounded-lg p-4 border border-accent-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-accent-700 font-semibold mb-1">
                                        Students
                                    </p>
                                    <p class="text-3xl font-bold text-accent-900">
                                        {{ course.students_count }}
                                    </p>
                                </div>
                                <i class="pi pi-users text-3xl text-accent-400"></i>
                            </div>
                        </div>

                        <div class="bg-darker-100 rounded-lg p-4 border border-darker-300">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-darker-600 font-semibold mb-1">
                                        Instructors
                                    </p>
                                    <p class="text-3xl font-bold text-darker-900">
                                        {{ course.instructors_count }}
                                    </p>
                                </div>
                                <i class="pi pi-user text-3xl text-darker-400"></i>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Instructors -->
                <Card class="shadow-md">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-user text-primary-600"></i>
                            <span class="text-lg">Instructors</span>
                        </div>
                    </template>
                    <template #content>
                        <UserList v-if="course.instructors && course.instructors.length > 0" :users="course.instructors" />
                        <div v-else class="text-center py-8 text-darker-500">
                            <i class="pi pi-users text-4xl mb-3 block"></i>
                            <p>No instructors assigned yet</p>
                        </div>
                    </template>
                </Card>

                <!-- Students -->
                <Card class="shadow-md">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-users text-accent-600"></i>
                            <span class="text-lg">Students</span>
                        </div>
                    </template>
                    <template #content>
                        <div v-if="course.students && course.students.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
                            <div
                                v-for="student in course.students"
                                :key="student.id"
                                class="flex items-center gap-3 p-3 bg-darker-50 rounded-lg"
                            >
                                <div class="w-10 h-10 rounded-full bg-accent-200 flex items-center justify-center">
                                    <i class="pi pi-user text-accent-700"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-darker-900">
                                        {{ student.first_name }} {{ student.last_name }}
                                    </p>
                                    <p class="text-sm text-darker-600">
                                        {{ student.email }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-darker-500">
                            <i class="pi pi-users text-4xl mb-3 block"></i>
                            <p>No students enrolled yet</p>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Pages -->
            <Card class="mt-6 shadow-md">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-file text-primary-600"></i>
                        <span class="text-lg">Course Pages</span>
                    </div>
                </template>
                <template #content>
                    <div v-if="course.pages && course.pages.length > 0" class="space-y-3">
                        <div
                            v-for="page in course.pages"
                            :key="page.id"
                            class="flex items-center justify-between p-4 bg-darker-50 rounded-lg hover:bg-darker-100 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-200 text-primary-700 font-semibold text-sm">
                                    {{ page.order }}
                                </span>
                                <div>
                                    <p class="font-semibold text-darker-900">
                                        {{ page.title }}
                                    </p>
                                    <p class="text-sm text-darker-600">
                                        Status: {{ page.status }}
                                    </p>
                                </div>
                            </div>
                            <Tag
                                :value="page.status"
                                :severity="getStatusSeverity(page.status)"
                            />
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-darker-500">
                        <i class="pi pi-file text-4xl mb-3 block"></i>
                        <p>No pages created yet</p>
                    </div>
                </template>
            </Card>

            <!-- Metadata -->
            <Card class="mt-6 shadow-md">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-info-circle text-darker-600"></i>
                        <span class="text-lg">Metadata</span>
                    </div>
                </template>
                <template #content>
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
                </template>
            </Card>

    </div>
  </AppLayout>
</template>

<style scoped>
/* Additional custom styles if needed */
</style>
