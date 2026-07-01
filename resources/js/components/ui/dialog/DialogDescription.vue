<script setup>
import { DialogDescription, useForwardProps } from "reka-ui";
import { cn } from "@/lib/utils";
const props = defineProps({ class: { type: null, required: false }, asChild: { type: Boolean, required: false }, as: { type: null, required: false } });
const delegatedProps = useForwardProps(props);
</script>

<template>
  <DialogDescription v-bind="delegatedProps" :class="cn('text-sm text-muted-foreground', props.class)"><slot /></DialogDescription>
</template>
