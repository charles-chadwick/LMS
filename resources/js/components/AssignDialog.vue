<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { UserPlus, Search, Check } from 'lucide-vue-next';
import {
    Dialog, DialogTrigger, DialogContent, DialogHeader,
    DialogFooter, DialogTitle, DialogDescription, DialogClose,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import Avatar from '@/components/Avatar.vue';
import { useDebouncedSearch } from '@/composables/useDebouncedSearch';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    searchUrl: { type: String, required: true },
    storeUrl: { type: String, required: true },
    variant: { type: String, default: 'primary' },
    triggerLabel: { type: String, default: 'Add' },
    triggerSize: { type: String, default: 'default' },
});

const open = ref(false);
const selected = ref([]); // array of user ids
const submitting = ref(false);
const { results: users, loading, search, reset } = useDebouncedSearch(props.searchUrl);

const fullName = (user) => `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();
const isSelected = (id) => selected.value.includes(id);

const toggle = (id) => {
    selected.value = isSelected(id)
        ? selected.value.filter((value) => value !== id)
        : [...selected.value, id];
};

const onOpenChange = (value) => {
    open.value = value;
    if (value) {
        selected.value = [];
        search('');
    } else {
        reset();
    }
};

const submit = () => {
    if (selected.value.length === 0) {
        return;
    }
    submitting.value = true;
    router.post(props.storeUrl, { user_ids: selected.value }, {
        preserveScroll: true,
        onSuccess: () => { open.value = false; },
        onFinish: () => { submitting.value = false; },
    });
};
</script>

<template>
  <Dialog :open="open" @update:open="onOpenChange">
    <DialogTrigger as-child>
      <Button :size="triggerSize">
        <UserPlus class="w-4 h-4" />
        {{ triggerLabel }}
      </Button>
    </DialogTrigger>
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription v-if="description">{{ description }}</DialogDescription>
      </DialogHeader>

      <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-darker-400" />
        <input
            type="text"
            class="w-full rounded-md border border-darker-200 bg-white py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400"
            placeholder="Search by name or email…"
            @input="search($event.target.value)"
        />
      </div>

      <div class="max-h-72 space-y-1 overflow-y-auto">
        <p v-if="loading" class="py-6 text-center text-sm text-darker-500">Searching…</p>
        <p v-else-if="users.length === 0" class="py-6 text-center text-sm text-darker-500">No matches found.</p>
        <button
            v-for="user in users"
            :key="user.id"
            type="button"
            class="flex w-full items-center justify-between gap-3 rounded-lg p-2 text-left hover:bg-darker-50"
            :class="isSelected(user.id) ? 'bg-primary-50 ring-1 ring-primary-200' : ''"
            @click="toggle(user.id)"
        >
          <span class="flex items-center gap-3">
            <Avatar :user="user" size="sm" :variant="variant" :zoomable="false" />
            <span>
              <span class="block font-medium text-darker-900">{{ fullName(user) }}</span>
              <span class="block text-xs text-darker-600">{{ user.email }}</span>
            </span>
          </span>
          <Check v-if="isSelected(user.id)" class="w-4 h-4 text-primary-600" />
        </button>
      </div>

      <DialogFooter>
        <DialogClose as-child>
          <Button variant="outline">Cancel</Button>
        </DialogClose>
        <Button :disabled="selected.length === 0 || submitting" @click="submit">
          Add<span v-if="selected.length"> ({{ selected.length }})</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
