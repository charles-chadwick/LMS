<script setup>
import { computed } from 'vue';
import {
    ComboboxRoot,
    ComboboxAnchor,
    ComboboxTrigger,
    ComboboxInput,
    ComboboxPortal,
    ComboboxContent,
    ComboboxViewport,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxEmpty,
} from 'reka-ui';
import { Check, ChevronDown, Search } from 'lucide-vue-next';
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
    <ComboboxRoot v-model="selectedValue">
        <ComboboxAnchor as-child>
            <ComboboxTrigger
                class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm text-start ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 [&>span]:truncate"
            >
                <span v-if="selectedUser" class="flex items-center gap-2">
                    <Avatar :user="selectedUser" size="sm" :variant="variant" :zoomable="false" />
                    {{ fullName(selectedUser) }}
                </span>
                <span v-else class="text-muted-foreground">{{ placeholder }}</span>
                <ChevronDown class="w-4 h-4 opacity-50 shrink-0" />
            </ComboboxTrigger>
        </ComboboxAnchor>

        <ComboboxPortal>
            <ComboboxContent
                position="popper"
                :side-offset="4"
                class="relative z-50 max-h-96 w-[--reka-combobox-trigger-width] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md"
            >
                <div class="flex items-center border-b px-3">
                    <Search class="w-4 h-4 mr-2 shrink-0 opacity-50" />
                    <ComboboxInput
                        class="flex h-10 w-full bg-transparent py-2 text-sm outline-none placeholder:text-muted-foreground"
                        placeholder="Search…"
                    />
                </div>
                <ComboboxViewport class="p-1">
                    <ComboboxEmpty class="py-6 text-center text-sm text-muted-foreground">
                        No users found.
                    </ComboboxEmpty>
                    <ComboboxItem
                        v-for="user in users"
                        :key="user.id"
                        :value="user.id"
                        class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
                    >
                        <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                            <ComboboxItemIndicator>
                                <Check class="h-4 w-4" />
                            </ComboboxItemIndicator>
                        </span>
                        <span class="flex items-center gap-2">
                            <Avatar :user="user" size="sm" :variant="variant" :zoomable="false" />
                            {{ fullName(user) }}
                        </span>
                    </ComboboxItem>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>
