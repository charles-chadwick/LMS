<script setup>
import { computed } from 'vue';
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
    users: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: String,
        default: 'Select a user…',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'accent', 'darker'].includes(value),
    },
});

const selectedValue = defineModel({ type: [Number, String, null], default: null });

const fullName = (user) => `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();

const selectedUser = computed(() =>
    props.users.find((user) => user.id === selectedValue.value) ?? null,
);
</script>

<template>
    <Combobox v-model="selectedValue">
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
            <ComboboxInput />
            <ComboboxList>
                <ComboboxEmpty>No users found.</ComboboxEmpty>
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
