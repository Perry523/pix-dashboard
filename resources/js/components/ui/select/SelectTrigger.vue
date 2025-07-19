<script setup lang="ts">
import { cn } from '@/lib/utils';
import { SelectTrigger as SelectTriggerPrimitive } from 'reka-ui';
import { ChevronDown } from 'lucide-vue-next';

interface Props {
    class?: string;
    disabled?: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <SelectTriggerPrimitive
        :class="cn(
            'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
            props.class
        )"
        :disabled="disabled"
    >
        <slot />
        <ChevronDown class="h-4 w-4 opacity-50" />
    </SelectTriggerPrimitive>
</template>
