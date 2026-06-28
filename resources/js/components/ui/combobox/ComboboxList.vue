<script setup>
import { reactiveOmit } from "@vueuse/core";
import { ComboboxViewport, useForwardProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps({
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
  class: { type: null, required: false },
});

const delegatedProps = reactiveOmit(props, "class");

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
  <ComboboxViewport
    v-bind="forwardedProps"
    :class="cn('max-h-72 overflow-y-auto p-1', props.class)"
  >
    <slot />
  </ComboboxViewport>
</template>
