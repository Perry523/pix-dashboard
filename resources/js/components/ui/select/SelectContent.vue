<script setup lang="ts">
import { cn } from '@/lib/utils';
import { SelectContent as SelectContentPrimitive, SelectPortal, SelectViewport } from 'reka-ui';

interface Props {
    class?: string;
    side?: 'top' | 'right' | 'bottom' | 'left';
    sideOffset?: number;
}

const props = withDefaults(defineProps<Props>(), {
    side: 'bottom',
    sideOffset: 4,
});
</script>

<template>
    <SelectPortal>
        <SelectContentPrimitive
            :class="cn(
                'relative z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
                props.class
            )"
            :side="side"
            :side-offset="sideOffset"
        >
            <SelectViewport class="p-1">
                <slot />
            </SelectViewport>
        </SelectContentPrimitive>
    </SelectPortal>
</template>
