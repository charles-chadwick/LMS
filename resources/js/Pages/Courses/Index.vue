<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { Select, InputText, Button, Card, IconField, InputIcon, ConfirmDialog, Tag }from 'primevue';

import Pagination from '@/Components/Pagination.vue';
import AppLayout from "@/Layouts/AppLayout.vue";

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

const confirm = useConfirm();

const search = ref(props.filters.search || '');
const status_filter = ref(props.filters.status || '');

const status_filter_options = [
    { label: 'All Statuses', value: '' },
    ...props.status_options,
];

watch([search, status_filter], () => {
    router.get(
        route('courses.index'),
        {
            search: search.value,
            status: status_filter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
});

const getStatusSeverity = (status) => {
    const severities = {
        'Published': 'success',
        'Draft': 'warn',
        'Archived': 'secondary',
    };
    return severities[status] || 'info';
};

const confirmDelete = (course) => {
    confirm.require({
        message: `Are you sure you want to delete "${course.title}"?`,
        header: 'Confirm Deletion',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Delete',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('courses.destroy', course.id), {
                preserveScroll: true,
            });
        },
    });
};

const viewCourse = (courseId) => {
    router.visit(route('courses.show', courseId));
};

const editCourse = (courseId) => {
    router.visit(route('courses.edit', courseId));
};

const createCourse = () => {
    router.visit(route('courses.create'));
};
</script>

<template>
  <AppLayout>
    <Head title="Courses" />

    <ConfirmDialog />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-darker-900">
                            Courses
                        </h1>
                        <p class="mt-2 text-sm text-darker-600">
                            Manage and organize your courses
                        </p>
                    </div>
                    <Button
                        label="Create Course"
                        icon="pi pi-plus"
                        @click="createCourse"
                        class="px-6"
                    />
                </div>
            </div>

            <!-- Filters -->
            <Card class="mb-6 shadow-md">
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                for="search"
                                class="block mb-2 font-semibold text-sm text-darker-700"
                            >
                                Search
                            </label>
                            <IconField>
                                <InputIcon class="pi pi-search" />
                                <InputText
                                    id="search"
                                    v-model="search"
                                    placeholder="Search by title or code..."
                                    class="w-full"
                                />
                            </IconField>
                        </div>

                        <div>
                            <label
                                for="status"
                                class="block mb-2 font-semibold text-sm text-darker-700"
                            >
                                Status
                            </label>
                            <Select
                                id="status"
                                v-model="status_filter"
                                :options="status_filter_options"
                                option-label="label"
                                option-value="value"
                                placeholder="Filter by status"
                                class="w-full"
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Table -->
            <Card class="shadow-lg">
                <template #content>
                    <div v-if="courses.data.length === 0" class="text-center py-12">
                        <i class="pi pi-inbox text-6xl text-darker-300 mb-4"></i>
                        <p class="text-darker-500 text-lg mb-2">No courses found</p>
                        <p class="text-darker-400 text-sm mb-6">
                            {{ search || status_filter ? 'Try adjusting your filters' : 'Get started by creating your first course' }}
                        </p>
                        <Button
                            v-if="!search && !status_filter"
                            label="Create Your First Course"
                            icon="pi pi-plus"
                            @click="createCourse"
                        />
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-darker-200">
                                    <th class="text-left py-4 px-4 font-semibold text-sm text-darker-700">
                                        Code
                                    </th>
                                    <th class="text-left py-4 px-4 font-semibold text-sm text-darker-700">
                                        Title
                                    </th>
                                    <th class="text-left py-4 px-4 font-semibold text-sm text-darker-700">
                                        Status
                                    </th>
                                    <th class="text-center py-4 px-4 font-semibold text-sm text-darker-700">
                                        Pages
                                    </th>
                                    <th class="text-center py-4 px-4 font-semibold text-sm text-darker-700">
                                        Students
                                    </th>
                                    <th class="text-center py-4 px-4 font-semibold text-sm text-darker-700">
                                        Instructors
                                    </th>
                                    <th class="text-right py-4 px-4 font-semibold text-sm text-darker-700">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="course in courses.data"
                                    :key="course.id"
                                    class="border-b border-darker-100 hover:bg-darker-50 transition-colors"
                                >
                                    <td class="py-4 px-4">
                                        <span class="font-mono text-sm text-darker-700 font-semibold">
                                            {{ course.code }}
                                        </span>
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
                                        <Tag
                                            :value="course.status"
                                            :severity="getStatusSeverity(course.status)"
                                        />
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
                                            <Button
                                                icon="pi pi-eye"
                                                severity="info"
                                                size="small"
                                                outlined
                                                @click="viewCourse(course.id)"
                                                v-tooltip.top="'View'"
                                            />
                                            <Button
                                                icon="pi pi-pencil"
                                                severity="secondary"
                                                size="small"
                                                outlined
                                                @click="editCourse(course.id)"
                                                v-tooltip.top="'Edit'"
                                            />
                                            <Button
                                                icon="pi pi-trash"
                                                severity="danger"
                                                size="small"
                                                outlined
                                                @click="confirmDelete(course)"
                                                v-tooltip.top="'Delete'"
                                            />
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
                </template>
            </Card>

    </div>
  </AppLayout>
</template>