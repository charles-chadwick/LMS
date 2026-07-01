<script setup>
import { onBeforeUnmount, onMounted, ref, nextTick, watch } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    maxHeight: {
        type: String,
        default: 'max-h-96',
    },
    hint: {
        type: String,
        default: 'Scroll for more',
    },
});

const scroll_container = ref(null);
const can_scroll_up = ref(false);
const can_scroll_down = ref(false);

const updateScrollState = () => {
    const element = scroll_container.value;
    if (!element) {
        return;
    }
    const { scrollTop, scrollHeight, clientHeight } = element;
    can_scroll_up.value = scrollTop > 1;
    can_scroll_down.value = scrollTop + clientHeight < scrollHeight - 1;
};

let resize_observer = null;

onMounted(async () => {
    await nextTick();
    updateScrollState();

    if (typeof ResizeObserver !== 'undefined' && scroll_container.value) {
        resize_observer = new ResizeObserver(() => updateScrollState());
        resize_observer.observe(scroll_container.value);
    }
});

onBeforeUnmount(() => {
    resize_observer?.disconnect();
});

watch(
    () => props.maxHeight,
    async () => {
        await nextTick();
        updateScrollState();
    },
);
</script>

<template>
    <div class="relative">
        <div
            ref="scroll_container"
            class="scrollable-list space-y-3 overflow-y-auto"
            :class="maxHeight"
            @scroll="updateScrollState"
        >
            <slot />
        </div>

        <!-- Top fade: content is hidden above -->
        <div
            v-show="can_scroll_up"
            class="pointer-events-none absolute inset-x-0 top-0 h-6 bg-gradient-to-b from-white to-transparent"
        ></div>

        <!-- Bottom fade + hint: content is hidden below -->
        <div
            v-show="can_scroll_down"
            class="pointer-events-none absolute inset-x-0 bottom-0 flex items-end justify-center pb-1 h-10 bg-gradient-to-t from-white to-transparent"
        >
            <span class="flex items-center gap-1 text-xs font-medium text-darker-500">
                <ChevronDown class="w-3.5 h-3.5 animate-bounce" />
                {{ hint }}
            </span>
        </div>
    </div>
</template>

<style scoped>
/*noinspection CssUnusedSymbol*/
.scrollable-list::-webkit-scrollbar {
    width: 6px;
}

.scrollable-list::-webkit-scrollbar-track {
    background: transparent;
}

.scrollable-list::-webkit-scrollbar-thumb {
    background: rgb(168 162 158 / 0.5);
    border-radius: 3px;
}

.scrollable-list::-webkit-scrollbar-thumb:hover {
    background: rgb(168 162 158 / 0.7);
}
</style>
