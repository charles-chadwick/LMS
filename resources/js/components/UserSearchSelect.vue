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
import { useDebouncedSearch } from '@/composables/useDebouncedSearch';

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
const selectedUser = ref(null);
const { results: users, loading, search } = useDebouncedSearch(props.searchUrl);

const fullName = (user) => `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();
const onSearchInput = (event) => search(event.target.value);
const onOpenChange = (isOpen) => {
    if (isOpen) {
        // Refetch each time the menu opens so excluded members stay current.
        search('');
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
