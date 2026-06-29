<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Trash2, UserPlus, UsersRound, X, Star, AlignLeft } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import Avatar from '@/components/Avatar.vue';
import ConfirmAction from '@/components/ConfirmAction.vue';
import UserSelect from '@/components/UserSelect.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    group: {
        type: Object,
        required: true,
    },
    assignable_users: {
        type: Array,
        default: () => [],
    },
    can: {
        type: Object,
        default: () => ({ update: false, delete: false, manage_members: false }),
    },
});

const canManageMembers = computed(() => props.can.manage_members);

const getTypeVariant = (type) => {
    const variants = {
        Instructor: 'secondary',
        Student: 'outline',
    };
    return variants[type] || 'secondary';
};

const selected_user_id = ref(null);
const add_as_leader = ref(false);

const addMember = () => {
    if (!selected_user_id.value) {
        return;
    }

    router.post(
        route('groups.members.store', props.group.id),
        { user_id: selected_user_id.value, is_leader: add_as_leader.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selected_user_id.value = null;
                add_as_leader.value = false;
            },
        },
    );
};

const toggleLeader = (member) => {
    router.put(
        route('groups.members.update', { group: props.group.id, user: member.id }),
        { is_leader: !member.pivot.is_leader },
        { preserveScroll: true },
    );
};

const removeMember = (member) => {
    router.delete(
        route('groups.members.destroy', { group: props.group.id, user: member.id }),
        { preserveScroll: true },
    );
};

const deleteGroup = () => {
    router.delete(route('groups.destroy', props.group.id));
};
</script>

<template>
  <AppLayout>
    <Head :title="group.name" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <!-- Back link -->
      <div class="mb-6">
        <Link
            :href="route('groups.index')"
            class="inline-flex items-center gap-2 text-sm text-darker-600 hover:text-darker-900"
        >
          <ArrowLeft class="w-4 h-4" />
          Back to Groups
        </Link>
      </div>

      <!-- Header -->
      <Card class="shadow-lg mb-6">
        <CardContent class="pt-6">
          <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-4">
              <span class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-primary-100 text-primary-700">
                <UsersRound class="w-7 h-7" />
              </span>
              <div>
                <h1 class="text-2xl font-bold text-darker-900">{{ group.name }}</h1>
                <Badge :variant="getTypeVariant(group.type)" class="mt-1">{{ group.type }}</Badge>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <Button v-if="can.update" variant="outline" @click="router.visit(route('groups.edit', group.id))">
                <Pencil class="w-4 h-4" />
                Edit
              </Button>
              <ConfirmAction
                  v-if="can.delete"
                  title="Delete group?"
                  :description="`Are you sure you want to delete &quot;${group.name}&quot;?`"
                  confirm-label="Delete"
                  @confirm="deleteGroup"
              >
                <Button variant="outline" class="text-destructive border-destructive hover:bg-destructive/10">
                  <Trash2 class="w-4 h-4" />
                  Delete
                </Button>
              </ConfirmAction>
            </div>
          </div>

          <div class="mt-8 flex items-start gap-3">
            <AlignLeft class="w-5 h-5 text-darker-400 mt-0.5" />
            <div>
              <p class="text-xs uppercase tracking-wide text-darker-500">Description</p>
              <p class="text-sm font-medium text-darker-900">{{ group.description }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Members -->
      <Card class="shadow-md">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-lg">
            <UsersRound class="w-5 h-5 text-primary-600" />
            Members
            <span class="text-sm font-normal text-darker-500">({{ group.users_count }})</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="group.users && group.users.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
            <div
                v-for="member in group.users"
                :key="member.id"
                class="flex items-center justify-between gap-3 p-3 bg-darker-50 rounded-lg"
            >
              <div class="flex items-center gap-3">
                <Avatar :user="member" variant="primary" />
                <div>
                  <p class="font-semibold text-darker-900 flex items-center gap-2">
                    {{ member.first_name }} {{ member.last_name }}
                    <Badge v-if="member.pivot.is_leader" variant="default" class="gap-1">
                      <Star class="w-3 h-3" />
                      Leader
                    </Badge>
                  </p>
                  <p class="text-sm text-darker-600">{{ member.email }}</p>
                </div>
              </div>

              <div v-if="canManageMembers" class="flex items-center gap-1">
                <Button
                    variant="ghost"
                    size="icon-sm"
                    :class="member.pivot.is_leader ? 'text-amber-500 hover:bg-amber-500/10' : 'text-darker-400 hover:bg-darker-200'"
                    :aria-label="member.pivot.is_leader ? `Demote ${member.first_name}` : `Promote ${member.first_name} to leader`"
                    :title="member.pivot.is_leader ? 'Remove leader' : 'Make leader'"
                    @click="toggleLeader(member)"
                >
                  <Star class="w-4 h-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="text-destructive hover:bg-destructive/10"
                    :aria-label="`Remove ${member.first_name} ${member.last_name}`"
                    title="Remove member"
                    @click="removeMember(member)"
                >
                  <X class="w-4 h-4" />
                </Button>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-darker-500">
            <UsersRound class="w-10 h-10 mb-3 mx-auto" />
            <p>No members yet</p>
          </div>

          <!-- Add member -->
          <div v-if="canManageMembers && assignable_users.length > 0" class="mt-4 pt-4 border-t border-darker-200">
            <div class="flex items-center gap-2">
              <div class="flex-1">
                <UserSelect
                    v-model="selected_user_id"
                    :users="assignable_users"
                    variant="primary"
                    :placeholder="`Select a ${group.type.toLowerCase()}…`"
                />
              </div>
              <Button :disabled="!selected_user_id" @click="addMember">
                <UserPlus class="w-4 h-4" />
                Add
              </Button>
            </div>
            <div class="flex items-center gap-2 mt-3">
              <Checkbox id="add_as_leader" v-model="add_as_leader" />
              <Label for="add_as_leader" class="text-sm text-darker-600">Add as leader</Label>
            </div>
          </div>
          <p
              v-else-if="canManageMembers"
              class="mt-4 pt-4 border-t border-darker-200 text-sm text-darker-500"
          >
            All eligible {{ group.type.toLowerCase() }}s are already members of this group.
          </p>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
