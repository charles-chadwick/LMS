<script setup>
import { Users } from 'lucide-vue-next';
import Avatar from '@/components/Avatar.vue';

defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'accent', 'darker'].includes(value),
    },
    emptyMessage: {
        type: String,
        default: 'No users found',
    },
    maxHeight: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div v-if="users && users.length > 0" class="space-y-3" :class="maxHeight ? `max-h-${maxHeight} overflow-y-auto` : ''">
        <div
            v-for="user in users"
            :key="user.id"
            class="flex items-center gap-3 p-3 bg-darker-50 rounded-lg hover:bg-darker-100 transition-colors"
        >
            <Avatar :user="user" :variant="variant" />
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-darker-900 truncate">
                    {{ user.first_name }} {{ user.last_name }}
                </p>
                <p class="text-sm text-darker-600 truncate">
                    {{ user.email }}
                </p>
            </div>
        </div>
    </div>
    <div v-else class="text-center py-8 text-darker-500">
        <Users class="w-10 h-10 mb-3 mx-auto" />
        <p>{{ emptyMessage }}</p>
    </div>
</template>

<style scoped>
/*noinspection CssUnusedSymbol*/
/* Ensure scrollbar styling if needed */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgb(168 162 158 / 0.5);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgb(168 162 158 / 0.7);
}
</style>
