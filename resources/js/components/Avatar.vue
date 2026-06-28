<script setup>
import { computed } from 'vue';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
    VisuallyHidden,
} from 'reka-ui';
import { X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'accent', 'darker'].includes(value),
    },
});

const sizeClasses = {
    sm: 'w-8 h-8 text-xs',
    md: 'w-10 h-10 text-sm',
    lg: 'w-12 h-12 text-base',
};

const variantClasses = {
    primary: 'bg-primary-200 text-primary-700',
    accent: 'bg-accent-200 text-accent-700',
    darker: 'bg-darker-200 text-darker-700',
};

const fullName = computed(() => `${props.user.first_name ?? ''} ${props.user.last_name ?? ''}`.trim());

const initials = computed(() => {
    const first = props.user.first_name?.[0] ?? '';
    const last = props.user.last_name?.[0] ?? '';
    return `${first}${last}`.toUpperCase() || '?';
});

const thumb = computed(() => props.user.avatar?.thumb ?? null);

const full = computed(() => props.user.avatar?.full ?? null);

const baseClasses = computed(() =>
    cn(
        'rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden font-semibold select-none',
        sizeClasses[props.size],
        variantClasses[props.variant],
    ),
);
</script>

<template>
    <!-- With avatar: clickable thumbnail that opens the full-size image in a modal -->
    <DialogRoot v-if="thumb">
        <DialogTrigger
            :class="cn(baseClasses, 'cursor-zoom-in transition-shadow hover:ring-2 hover:ring-primary-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500')"
            :aria-label="`View ${fullName}'s avatar`"
        >
            <img :src="thumb" :alt="fullName" class="w-full h-full object-cover" />
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-50 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
            />
            <DialogContent
                class="fixed left-1/2 top-1/2 z-50 w-full max-w-md -translate-x-1/2 -translate-y-1/2 rounded-lg bg-background p-4 shadow-lg duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95"
            >
                <DialogTitle class="mb-3 text-center text-lg font-semibold text-darker-900">
                    {{ fullName }}
                </DialogTitle>
                <VisuallyHidden as-child>
                    <DialogDescription>Full size avatar for {{ fullName }}</DialogDescription>
                </VisuallyHidden>

                <img :src="full" :alt="fullName" class="w-full rounded-md object-contain" />

                <DialogClose
                    class="absolute right-3 top-3 rounded-full p-1 text-darker-500 hover:bg-darker-100 hover:text-darker-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    aria-label="Close"
                >
                    <X class="w-5 h-5" />
                </DialogClose>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>

    <!-- Without avatar: initials fallback -->
    <div v-else :class="baseClasses" :aria-label="fullName" role="img">
        {{ initials }}
    </div>
</template>
