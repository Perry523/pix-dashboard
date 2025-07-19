<template>
  <ScrollAreaRoot
    :class="cn('relative overflow-hidden', props.class)"
    v-bind="forwarded"
  >
    <ScrollAreaViewport class="h-full w-full rounded-[inherit]">
      <slot />
    </ScrollAreaViewport>
    <ScrollBar />
    <ScrollAreaCorner />
  </ScrollAreaRoot>
</template>

<script setup lang="ts">
import { ScrollAreaCorner, ScrollAreaRoot, ScrollAreaViewport } from 'radix-vue';
import { type HTMLAttributes, computed } from 'vue';
import { cn } from '@/lib/utils';
import ScrollBar from './ScrollBar.vue';

interface Props {
  class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {});

const forwarded = computed(() => {
  const { class: _, ...rest } = props;
  return rest;
});
</script>
