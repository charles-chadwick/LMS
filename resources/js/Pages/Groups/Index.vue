<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, Search, Inbox, Eye, Pencil, Trash2, UsersRound } from 'lucide-vue-next';
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
    groups: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    type_options: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const canCreateGroups = computed(() => page.props.auth?.can?.create_groups ?? false);

const search = ref(props.filters.search || '');
const type_filter = ref(props.filters.type || 'all');

const type_filter_options = [
    { label: 'All Types', value: 'all' },
    ...props.type_options,
];

watch([search, type_filter], () => {
    router.get(
        route('groups.index'),
        {
            search: search.value,
            type: type_filter.value === 'all' ? '' : type_filter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
});

const getTypeVariant = (type) => {
    const variants = {
        General: 'secondary',
        Private: 'outline',
    };
    return variants[type] || 'secondary';
};

const viewGroup = (groupId) => {
    router.visit(route('groups.show', groupId));
};

const editGroup = (groupId) => {
    router.visit(route('groups.edit', groupId));
};

const deleteGroup = (group) => {
    router.delete(route('groups.destroy', group.id), { preserveScroll: true });
};

const createGroup = () => {
    router.visit(route('groups.create'));
};
</script>

<template>
  <AppLayout>
    <Head title="Groups" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-darker-900">Groups</h1>
            <p class="mt-2 text-sm text-darker-600">Organize instructors and students into groups</p>
          </div>
          <Button v-if="canCreateGroups" class="px-6" @click="createGroup">
            <Plus class="w-4 h-4" />
            Create Group
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
                    placeholder="Search by name or description..."
                    class="w-full pl-9"
                />
              </div>
            </div>

            <div>
              <Label for="type" class="mb-2">Type</Label>
              <Select id="type" v-model="type_filter">
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Filter by type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                      v-for="option in type_filter_options"
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
          <div v-if="groups.data.length === 0" class="text-center py-12">
            <Inbox class="w-16 h-16 text-darker-300 mb-4 mx-auto" />
            <p class="text-darker-500 text-lg mb-2">No groups found</p>
            <p class="text-darker-400 text-sm mb-6">
              {{ search || type_filter !== 'all' ? 'Try adjusting your filters' : 'Get started by creating your first group' }}
            </p>
            <Button v-if="!search && type_filter === 'all' && canCreateGroups" @click="createGroup">
              <Plus class="w-4 h-4" />
              Create Your First Group
            </Button>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-darker-200">
                  <th class="text-left py-4 px-4 font-semibold text-sm text-darker-700">Name</th>
                  <th class="text-left py-4 px-4 font-semibold text-sm text-darker-700">Type</th>
                  <th class="text-left py-4 px-4 font-semibold text-sm text-darker-700">Description</th>
                  <th class="text-center py-4 px-4 font-semibold text-sm text-darker-700">Members</th>
                  <th class="text-right py-4 px-4 font-semibold text-sm text-darker-700">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                    v-for="group in groups.data"
                    :key="group.id"
                    class="border-b border-darker-100 hover:bg-darker-50 transition-colors"
                >
                  <td class="py-4 px-4">
                    <div class="flex items-center gap-3">
                      <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-primary-100 text-primary-700">
                        <UsersRound class="w-4 h-4" />
                      </span>
                      <Link
                          :href="route('groups.show', group.id)"
                          class="text-primary-600 hover:text-primary-800 font-medium hover:underline"
                      >
                        {{ group.name }}
                      </Link>
                    </div>
                  </td>
                  <td class="py-4 px-4">
                    <Badge :variant="getTypeVariant(group.type)">{{ group.type }}</Badge>
                  </td>
                  <td class="py-4 px-4">
                    <span class="text-sm text-darker-700 line-clamp-1">{{ group.description }}</span>
                  </td>
                  <td class="py-4 px-4 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-700 text-sm font-semibold">
                      {{ group.users_count }}
                    </span>
                  </td>
                  <td class="py-4 px-4">
                    <div class="flex items-center justify-end gap-2">
                      <Button variant="outline" size="icon-sm" aria-label="View" title="View" @click="viewGroup(group.id)">
                        <Eye class="w-4 h-4" />
                      </Button>
                      <Button v-if="group.can_update" variant="outline" size="icon-sm" aria-label="Edit" title="Edit" @click="editGroup(group.id)">
                        <Pencil class="w-4 h-4" />
                      </Button>
                      <ConfirmAction
                          v-if="group.can_delete"
                          title="Delete group?"
                          :description="`Are you sure you want to delete &quot;${group.name}&quot;?`"
                          confirm-label="Delete"
                          @confirm="deleteGroup(group)"
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
          <div v-if="groups.data.length > 0" class="mt-6 pt-6 border-t border-darker-200">
            <Pagination :pagination="groups" />
          </div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
