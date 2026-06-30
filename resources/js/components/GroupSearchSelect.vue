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

const props = defineProps({
    searchUrl: {
        type: String,
        required: true,
    },
    placeholder: {
        type: String,
        default: 'Search for a group…',
    },
});

const selectedValue = defineModel({ type: [Number, String, null], default: null });

const groups = ref([]);
const selectedGroup = ref(null);
const loading = ref(false);

const fetchGroups = async (term) => {
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

        groups.value = response.ok ? await response.json() : [];
    } catch (error) {
        groups.value = [];
    } finally {
        loading.value = false;
    }
};

let debounceTimer = null;

const onSearchInput = (event) => {
    const term = event.target.value;

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchGroups(term), 250);
};

const onOpenChange = (isOpen) => {
    if (isOpen) {
        fetchGroups('');
    }
};

watch(selectedValue, (value) => {
    if (!value) {
        selectedGroup.value = null;
        return;
    }

    const match = groups.value.find((group) => group.id === value);

    if (match) {
        selectedGroup.value = match;
    }
});
</script>

<template>
    <Combobox v-model="selectedValue" :ignore-filter="true" @update:open="onOpenChange">
        <ComboboxAnchor as-child>
            <ComboboxTrigger>
                <span v-if="selectedGroup" class="truncate">
                    {{ selectedGroup.name }}
                </span>
                <span v-else class="text-muted-foreground">{{ placeholder }}</span>
            </ComboboxTrigger>
        </ComboboxAnchor>

        <ComboboxContent>
            <ComboboxInput :placeholder="placeholder" @input="onSearchInput" />
            <ComboboxList>
                <ComboboxEmpty>
                    {{ loading ? 'Searching…' : 'No groups found.' }}
                </ComboboxEmpty>
                <ComboboxItem
                    v-for="group in groups"
                    :key="group.id"
                    :value="group.id"
                >
                    <span class="flex flex-col">
                        <span class="font-medium">{{ group.name }}</span>
                        <span v-if="group.description" class="text-sm text-muted-foreground truncate">
                            {{ group.description }}
                        </span>
                    </span>
                </ComboboxItem>
            </ComboboxList>
        </ComboboxContent>
    </Combobox>
</template>
