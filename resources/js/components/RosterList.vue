<script setup>
import { computed, ref, watch } from 'vue';
import { Search, Users } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import Avatar from '@/components/Avatar.vue';
import ScrollableList from '@/components/ScrollableList.vue';

const props = defineProps({
    searchUrl: { type: String, required: true },
    initialItems: { type: Array, default: () => [] },
    totalCount: { type: Number, default: 0 },
    variant: { type: String, default: 'primary' },
    canSearch: { type: Boolean, default: false },
    searchPlaceholder: { type: String, default: 'Search by name or email…' },
    emptyText: { type: String, default: 'No one here yet' },
    scrollHint: { type: String, default: 'Scroll for more' },
});

const items = ref([...props.initialItems]);
const total = ref(props.totalCount);
const current_page = ref(1);
const active_term = ref('');
const loading = ref(false);
let debounce_timer = null;

const fullName = (user) => `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();
const hasMore = computed(() => items.value.length < total.value);
const isSearching = computed(() => active_term.value.length > 0);

const fetchPage = async (page) => {
    loading.value = true;
    try {
        const url = new URL(props.searchUrl, window.location.origin);
        if (active_term.value) {
            url.searchParams.set('search', active_term.value);
        }
        url.searchParams.set('page', page);
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            return;
        }
        const payload = await response.json();
        const data = payload.data ?? [];
        items.value = page === 1 ? data : [...items.value, ...data];
        current_page.value = payload.current_page ?? page;
        total.value = payload.total ?? items.value.length;
    } finally {
        loading.value = false;
    }
};

const restoreInitial = () => {
    active_term.value = '';
    items.value = [...props.initialItems];
    total.value = props.totalCount;
    current_page.value = 1;
};

const search = (term) => {
    clearTimeout(debounce_timer);
    const trimmed = term.trim();
    debounce_timer = setTimeout(() => {
        active_term.value = trimmed;
        if (trimmed) {
            fetchPage(1);
        } else {
            restoreInitial();
        }
    }, 250);
};

const loadMore = () => {
    if (!loading.value && hasMore.value) {
        fetchPage(current_page.value + 1);
    }
};

// Keep the roster in sync when the parent reloads it (e.g. after a removal or
// a newly added member). While a search is active we re-run it so the results
// reflect the change; otherwise we reset to the freshly provided first page.
watch(
    () => props.initialItems,
    () => {
        if (isSearching.value) {
            fetchPage(1);
        } else {
            restoreInitial();
        }
    },
);
</script>

<template>
  <div>
    <div v-if="canSearch" class="relative mb-3">
      <Search class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-darker-400" />
      <input
          type="text"
          class="w-full rounded-md border border-darker-200 bg-white py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400"
          :placeholder="searchPlaceholder"
          @input="search($event.target.value)"
      />
    </div>

    <ScrollableList v-if="items.length > 0" :hint="scrollHint">
      <div
          v-for="item in items"
          :key="item.id"
          class="flex items-center justify-between gap-3 p-3 bg-darker-50 rounded-lg"
      >
        <div class="flex items-center gap-3">
          <Avatar :user="item" :variant="variant" />
          <div>
            <p class="font-semibold text-darker-900">
              {{ fullName(item) }}
            </p>
            <p class="text-sm text-darker-600">{{ item.email }}</p>
          </div>
        </div>
        <slot name="actions" :item="item" />
      </div>

      <div v-if="hasMore" class="pt-1 text-center">
        <Button variant="ghost" size="sm" :disabled="loading" @click="loadMore">
          {{ loading ? 'Loading…' : 'Load more' }}
        </Button>
      </div>
    </ScrollableList>

    <div v-else-if="loading" class="py-8 text-center text-darker-500">
      <p>Searching…</p>
    </div>

    <div v-else-if="isSearching" class="py-8 text-center text-darker-500">
      <Search class="w-10 h-10 mb-3 mx-auto" />
      <p>No matches found</p>
    </div>

    <div v-else class="text-center py-8 text-darker-500">
      <Users class="w-10 h-10 mb-3 mx-auto" />
      <p>{{ emptyText }}</p>
    </div>
  </div>
</template>
