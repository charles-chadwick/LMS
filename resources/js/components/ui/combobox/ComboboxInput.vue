<script setup>
import { reactiveOmit } from "@vueuse/core";
import { Search } from "lucide-vue-next";
import { ComboboxInput } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps({
  placeholder: { type: String, required: false, default: "Search…" },
  autoFocus: { type: Boolean, required: false, default: true },
  class: { type: null, required: false },
});

const delegatedProps = reactiveOmit(props, "class");
</script>

<template>
  <div class="flex items-center border-b px-3">
    <Search class="w-4 h-4 mr-2 shrink-0 opacity-50" />
    <ComboboxInput
      v-bind="delegatedProps"
      :class="
        cn(
          'flex h-10 w-full bg-transparent py-2 text-sm outline-none placeholder:text-muted-foreground',
          props.class,
        )
      "
    />
  </div>
</template>
