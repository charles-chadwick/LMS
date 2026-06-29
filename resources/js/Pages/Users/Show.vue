<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Trash2, Mail, BadgeCheck, BookOpen } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import Avatar from '@/components/Avatar.vue';
import ConfirmAction from '@/components/ConfirmAction.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    can: {
        type: Object,
        default: () => ({}),
    },
});

const getRoleVariant = (role) => {
    const variants = {
        Admin: 'default',
        Instructor: 'secondary',
        Student: 'outline',
    };
    return variants[role] || 'secondary';
};

const deleteUser = () => {
    router.delete(route('users.destroy', props.user.id));
};
</script>

<template>
  <AppLayout>
    <Head :title="`${user.first_name} ${user.last_name}`" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <!-- Back Button -->
      <div class="mb-6">
        <Button variant="outline" @click="router.visit(route('users.index'))">
          <ArrowLeft class="w-4 h-4" />
          Back to Users
        </Button>
      </div>

      <Card class="shadow-lg">
        <CardContent class="pt-6">
          <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-4">
              <Avatar :user="user" size="lg" />
              <div>
                <h1 class="text-2xl font-bold text-darker-900">
                  {{ user.first_name }} {{ user.last_name }}
                </h1>
                <Badge :variant="getRoleVariant(user.role)" class="mt-1">{{ user.role }}</Badge>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <Button v-if="can.update" variant="outline" @click="router.visit(route('users.edit', user.id))">
                <Pencil class="w-4 h-4" />
                Edit
              </Button>
              <ConfirmAction
                  v-if="can.delete"
                  title="Delete user?"
                  :description="`Are you sure you want to delete &quot;${user.first_name} ${user.last_name}&quot;?`"
                  confirm-label="Delete"
                  @confirm="deleteUser"
              >
                <Button variant="outline" class="text-destructive border-destructive hover:bg-destructive/10">
                  <Trash2 class="w-4 h-4" />
                  Delete
                </Button>
              </ConfirmAction>
            </div>
          </div>

          <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="flex items-center gap-3">
              <Mail class="w-5 h-5 text-darker-400" />
              <div>
                <p class="text-xs uppercase tracking-wide text-darker-500">Email</p>
                <p class="text-sm font-medium text-darker-900">{{ user.email }}</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <BadgeCheck class="w-5 h-5 text-darker-400" />
              <div>
                <p class="text-xs uppercase tracking-wide text-darker-500">Verified</p>
                <p class="text-sm font-medium text-darker-900">
                  {{ user.email_verified_at ? 'Yes' : 'No' }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <BookOpen class="w-5 h-5 text-darker-400" />
              <div>
                <p class="text-xs uppercase tracking-wide text-darker-500">Courses</p>
                <p class="text-sm font-medium text-darker-900">{{ user.courses_count ?? 0 }}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
