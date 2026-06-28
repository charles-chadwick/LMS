<script setup>
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
} from '@/components/ui/select';
import Avatar from '@/components/Avatar.vue';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: [Number, String, null],
        default: null,
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

const emit = defineEmits(['update:modelValue']);

const fullName = (user) => `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();

const selectedUser = computed(() =>
    props.users.find((user) => user.id === props.modelValue) ?? null,
);

const selectedValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});
</script>

<template>
    <Select v-model="selectedValue">
        <SelectTrigger class="w-full">
            <span v-if="selectedUser" class="flex items-center gap-2">
                <Avatar :user="selectedUser" size="sm" :variant="variant" :zoomable="false" />
                {{ fullName(selectedUser) }}
            </span>
            <span v-else class="text-muted-foreground">{{ placeholder }}</span>
        </SelectTrigger>
        <SelectContent>
            <SelectItem
                v-for="user in users"
                :key="user.id"
                :value="user.id"
                :text-value="fullName(user)"
            >
                <span class="flex items-center gap-2">
                    <Avatar :user="user" size="sm" :variant="variant" :zoomable="false" />
                    {{ fullName(user) }}
                </span>
            </SelectItem>
        </SelectContent>
    </Select>
</template>
