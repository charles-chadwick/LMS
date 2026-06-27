<script setup>
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { Button, Card, ConfirmDialog, Tag } from 'primevue';
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    page: {
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

const editPage = () => {
    router.visit(route('pages.edit', props.page.id));
};

const backToCourse = () => {
    router.visit(route('courses.show', props.page.course_id));
};

const confirmDelete = () => {
    confirm.require({
        message: `Are you sure you want to delete "${props.page.title}"?`,
        header: 'Confirm Deletion',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Delete',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('pages.destroy', props.page.id));
        },
    });
};
</script>

<template>
  <AppLayout>
    <Head :title="page.title" />

    <ConfirmDialog />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <!-- Back Button -->
      <div class="mb-6">
        <Button
            label="Back to Course"
            icon="pi pi-arrow-left"
            severity="secondary"
            outlined
            @click="backToCourse"
        />
      </div>

      <!-- Page Header -->
      <Card class="mb-6 shadow-lg">
        <template #content>
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-3">
                <h1 class="text-4xl font-bold text-darker-900">
                  {{ page.title }}
                </h1>
                <Tag
                    :value="page.status"
                    :severity="getStatusSeverity(page.status)"
                    class="text-sm"
                />
              </div>
              <div v-if="page.course" class="flex items-center gap-2 text-darker-600">
                <i class="pi pi-book"></i>
                <span class="font-semibold">
                  {{ page.course.title }}
                </span>
                <span class="font-mono text-darker-500">
                  ({{ page.course.code }})
                </span>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <Button
                  label="Edit"
                  icon="pi pi-pencil"
                  severity="secondary"
                  @click="editPage"
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
        </template>
      </Card>

      <!-- Page Content -->
      <Card class="shadow-md">
        <template #content>
          <div class="prose max-w-none" v-html="page.content"></div>
        </template>
      </Card>

    </div>
  </AppLayout>
</template>

<style scoped>
/* Additional custom styles if needed */
</style>
