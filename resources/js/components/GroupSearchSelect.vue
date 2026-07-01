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
import { useDebouncedSearch } from '@/composables/useDebouncedSearch';

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
const selectedGroup = ref(null);
const { results: groups, loading, search } = useDebouncedSearch(props.searchUrl);

const onSearchInput = (event) => search(event.target.value);
const onOpenChange = (isOpen) => {
    if (isOpen) {
        search('');
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
