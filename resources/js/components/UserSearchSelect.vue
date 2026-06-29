<script setup>
import { ref, watch } from 'vue';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxList,
    ComboboxTrigger,
} from '@/components/ui/combobox';
import Avatar from '@/components/Avatar.vue';

const props = defineProps({
    searchUrl: {
        type: String,
        required: true,
    },
    placeholder: {
        type: String,
        default: 'Search for a user…',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'accent', 'darker'].includes(value),
    },
});

const selectedValue = defineModel({ type: [Number, String, null], default: null });

const users = ref([]);
const selectedUser = ref(null);
const loading = ref(false);

const fullName = (user) => `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();

const fetchUsers = async (term) => {
    loading.value = true;

    try {
        const url = new URL(props.searchUrl, window.location.origin);

        if (term) {
            url.searchParams.set('search', term);
        }

        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        users.value = response.ok ? await response.json() : [];
    } catch (error) {
        users.value = [];
    } finally {
        loading.value = false;
    }
};

let debounceTimer = null;

const onSearchInput = (event) => {
    const term = event.target.value;

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchUsers(term), 250);
};

const onOpenChange = (isOpen) => {
    if (isOpen) {
        // Refetch each time the menu opens so excluded members stay current.
        fetchUsers('');
    }
};

watch(selectedValue, (value) => {
    if (!value) {
        selectedUser.value = null;
        return;
    }

    const match = users.value.find((user) => user.id === value);

    if (match) {
        selectedUser.value = match;
    }
});
</script>

<template>
    <Combobox v-model="selectedValue" :ignore-filter="true" @update:open="onOpenChange">
        <ComboboxAnchor as-child>
            <ComboboxTrigger>
                <span v-if="selectedUser" class="flex items-center gap-2">
                    <Avatar :user="selectedUser" size="sm" :variant="variant" :zoomable="false" />
                    {{ fullName(selectedUser) }}
                </span>
                <span v-else class="text-muted-foreground">{{ placeholder }}</span>
            </ComboboxTrigger>
        </ComboboxAnchor>

        <ComboboxContent>
            <ComboboxInput :placeholder="placeholder" @input="onSearchInput" />
            <ComboboxList>
                <ComboboxEmpty>
                    {{ loading ? 'Searching…' : 'No users found.' }}
                </ComboboxEmpty>
                <ComboboxItem
                    v-for="user in users"
                    :key="user.id"
                    :value="user.id"
                >
                    <span class="flex items-center gap-2">
                        <Avatar :user="user" size="sm" :variant="variant" :zoomable="false" />
                        {{ fullName(user) }}
                    </span>
                </ComboboxItem>
            </ComboboxList>
        </ComboboxContent>
    </Combobox>
</template>
