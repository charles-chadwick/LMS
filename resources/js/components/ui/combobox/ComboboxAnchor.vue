<script setup>
import { reactiveOmit } from "@vueuse/core";
import { ComboboxAnchor, useForwardProps } from "reka-ui";
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
  <ComboboxAnchor v-bind="forwardedProps" :class="cn('relative w-full', props.class)">
    <slot />
  </ComboboxAnchor>
</template>
