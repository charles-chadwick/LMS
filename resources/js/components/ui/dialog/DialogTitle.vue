<script setup>
import { DialogTitle, useForwardProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps({ class: { type: null, required: false }, asChild: { type: Boolean, required: false }, as: { type: null, required: false } });
const delegatedProps = useForwardProps(props);
</script>

<template>
  <DialogTitle v-bind="delegatedProps" :class="cn('text-lg font-semibold text-darker-900', props.class)"><slot /></DialogTitle>
</template>
