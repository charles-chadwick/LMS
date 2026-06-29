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
import Avatar from '@/components/Avatar.vue';
import ConfirmAction from '@/components/ConfirmAction.vue';
import Pagination from '@/components/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    role_options: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const canCreateUsers = computed(() => page.props.auth?.can?.create_users ?? false);

const search = ref(props.filters.search || '');
const role_filter = ref(props.filters.role || 'all');

const role_filter_options = [
    { label: 'All Roles', value: 'all' },
    ...props.role_options,
];

watch([search, role_filter], () => {
    router.get(
        route('users.index'),
        {
            search: search.value,
            role: role_filter.value === 'all' ? '' : role_filter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
});

const getRoleVariant = (role) => {
    const variants = {
        Admin: 'default',
        Instructor: 'secondary',
        Student: 'outline',
    };
    return variants[role] || 'secondary';
};

const viewUser = (userId) => {
    router.visit(route('users.show', userId));
};

const editUser = (userId) => {
    router.visit(route('users.edit', userId));
};

const deleteUser = (user) => {
    router.delete(route('users.destroy', user.id), { preserveScroll: true });
};

const createUser = () => {
    router.visit(route('users.create'));
};
</script>

<template>
  <AppLayout>
    <Head title="Users" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-darker-900">Users</h1>
            <p class="mt-2 text-sm text-darker-600">Manage people and their roles</p>
          </div>
          <Button v-if="canCreateUsers" class="px-6" @click="createUser">
            <Plus class="w-4 h-4" />
            Create User
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
                    placeholder="Search by name or email..."
                    class="w-full pl-9"
                />
              </div>
            </div>

            <div>
              <Label for="role" class="mb-2">Role</Label>
              <Select id="role" v-model="role_filter">
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Filter by role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                      v-for="option in role_filter_options"
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
          <div v-if="users.data.length === 0" class="text-center py-12">
            <Inbox class="w-16 h-16 text-darker-300 mb-4 mx-auto" />
            <p class="text-darker-500 text-lg mb-2">No users found</p>
            <p class="text-darker-400 text-sm mb-6">
              {{ search || role_filter !== 'all' ? 'Try adjusting your filters' : 'Get started by creating your first user' }}
            </p>
            <Button v-if="!search && role_filter === 'all' && canCreateUsers" @click="createUser">
              <Plus class="w-4 h-4" />
              Create Your First User
            </Button>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-darker-200">
                  <th class="text-left py-4 px-4 font-semibold text-sm text-darker-700">Name</th>
                  <th class="text-left py-4 px-4 font-semibold text-sm text-darker-700">Email</th>
                  <th class="text-left py-4 px-4 font-semibold text-sm text-darker-700">Role</th>
                  <th class="text-center py-4 px-4 font-semibold text-sm text-darker-700">Courses</th>
                  <th class="text-right py-4 px-4 font-semibold text-sm text-darker-700">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                    v-for="user in users.data"
                    :key="user.id"
                    class="border-b border-darker-100 hover:bg-darker-50 transition-colors"
                >
                  <td class="py-4 px-4">
                    <div class="flex items-center gap-3">
                      <Avatar :user="user" size="sm" :zoomable="false" />
                      <Link
                          :href="route('users.show', user.id)"
                          class="text-primary-600 hover:text-primary-800 font-medium hover:underline"
                      >
                        {{ user.first_name }} {{ user.last_name }}
                      </Link>
                    </div>
                  </td>
                  <td class="py-4 px-4">
                    <span class="text-sm text-darker-700">{{ user.email }}</span>
                  </td>
                  <td class="py-4 px-4">
                    <Badge :variant="getRoleVariant(user.role)">{{ user.role }}</Badge>
                  </td>
                  <td class="py-4 px-4 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-700 text-sm font-semibold">
                      {{ user.courses_count }}
                    </span>
                  </td>
                  <td class="py-4 px-4">
                    <div class="flex items-center justify-end gap-2">
                      <Button variant="outline" size="icon-sm" aria-label="View" title="View" @click="viewUser(user.id)">
                        <Eye class="w-4 h-4" />
                      </Button>
                      <Button v-if="user.can_update" variant="outline" size="icon-sm" aria-label="Edit" title="Edit" @click="editUser(user.id)">
                        <Pencil class="w-4 h-4" />
                      </Button>
                      <ConfirmAction
                          v-if="user.can_delete"
                          title="Delete user?"
                          :description="`Are you sure you want to delete &quot;${user.first_name} ${user.last_name}&quot;?`"
                          confirm-label="Delete"
                          @confirm="deleteUser(user)"
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
          <div v-if="users.data.length > 0" class="mt-6 pt-6 border-t border-darker-200">
            <Pagination :pagination="users" />
          </div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
