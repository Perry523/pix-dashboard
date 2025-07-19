<script setup lang="ts">
import { SelectRoot } from 'reka-ui';

interface Props {
    value?: string;
    defaultValue?: string;
    disabled?: boolean;
}

interface Emits {
    (e: 'update:value', value: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
</script>

<template>
    <SelectRoot 
        :value="value" 
        :default-value="defaultValue"
        :disabled="disabled"
        @update:value="emit('update:value', $event)"
    >
        <slot />
    </SelectRoot>
</template>
