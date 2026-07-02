<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Users, Search, Check } from 'lucide-vue-next';
import {
    Dialog, DialogTrigger, DialogContent, DialogHeader,
    DialogFooter, DialogTitle, DialogDescription, DialogClose,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useDebouncedSearch } from '@/composables/useDebouncedSearch';

const props = defineProps({
    title: { type: String, required: true },
    searchUrl: { type: String, required: true },
    storeUrl: { type: String, required: true },
    triggerLabel: { type: String, default: 'Add a group' },
    triggerSize: { type: String, default: 'default' },
});

const open = ref(false);
const selected = ref([]);
const submitting = ref(false);
const { results: groups, loading, search, reset } = useDebouncedSearch(props.searchUrl);

const isSelected = (id) => selected.value.includes(id);
const toggle = (id) => {
    selected.value = isSelected(id)
        ? selected.value.filter((value) => value !== id)
        : [...selected.value, id];
};

const onOpenChange = (value) => {
    open.value = value;
    if (value) { selected.value = []; search(''); } else { reset(); }
};

const submit = () => {
    if (selected.value.length === 0) { return; }
    submitting.value = true;
    router.post(props.storeUrl, { group_ids: selected.value }, {
        preserveScroll: true,
        onSuccess: () => { open.value = false; },
        onFinish: () => { submitting.value = false; },
    });
};
</script>

<template>
  <Dialog :open="open" @update:open="onOpenChange">
    <DialogTrigger as-child>
      <Button variant="outline" :size="triggerSize">
        <Users class="w-4 h-4" />
        {{ triggerLabel }}
      </Button>
    </DialogTrigger>
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>Every current student member of the selected group(s) will be enrolled.</DialogDescription>
      </DialogHeader>

      <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-darker-400" />
        <input
            type="text"
            class="w-full rounded-md border border-darker-200 bg-white py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400"
            placeholder="Search groups…"
            @input="search($event.target.value)"
        />
      </div>

      <div class="max-h-72 space-y-1 overflow-y-auto">
        <p v-if="loading" class="py-6 text-center text-sm text-darker-500">Searching…</p>
        <p v-else-if="groups.length === 0" class="py-6 text-center text-sm text-darker-500">No groups found.</p>
        <button
            v-for="group in groups"
            :key="group.id"
            type="button"
            class="flex w-full items-center justify-between gap-3 rounded-lg p-2 text-left hover:bg-darker-50"
            :class="isSelected(group.id) ? 'bg-primary-50 ring-1 ring-primary-200' : ''"
            @click="toggle(group.id)"
        >
          <span>
            <span class="block font-medium text-darker-900">{{ group.name }}</span>
            <span v-if="group.description" class="block text-xs text-darker-600 truncate">{{ group.description }}</span>
          </span>
          <Check v-if="isSelected(group.id)" class="w-4 h-4 text-primary-600" />
        </button>
      </div>

      <DialogFooter>
        <DialogClose as-child>
          <Button variant="outline">Cancel</Button>
        </DialogClose>
        <Button :disabled="selected.length === 0 || submitting" @click="submit">
          Enroll<span v-if="selected.length"> ({{ selected.length }})</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
