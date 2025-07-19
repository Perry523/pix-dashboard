<template>
  <ScrollAreaScrollbar
    v-bind="forwarded"
    :class="cn(
      'flex touch-none select-none transition-colors',
      orientation === 'vertical' &&
        'h-full w-2.5 border-l border-l-transparent p-[1px]',
      orientation === 'horizontal' &&
        'h-2.5 flex-col border-t border-t-transparent p-[1px]',
      props.class
    )"
  >
    <ScrollAreaThumb class="relative flex-1 rounded-full bg-border" />
  </ScrollAreaScrollbar>
</template>

<script setup lang="ts">
import { ScrollAreaScrollbar, ScrollAreaThumb } from 'radix-vue';
import { type HTMLAttributes, computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
  class?: HTMLAttributes['class'];
  orientation?: 'vertical' | 'horizontal';
}

const props = withDefaults(defineProps<Props>(), {
  orientation: 'vertical',
});

const forwarded = computed(() => {
  const { class: _, ...rest } = props;
  return rest;
});
</script>
