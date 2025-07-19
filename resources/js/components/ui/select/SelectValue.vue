<script setup lang="ts">
import { SelectValue as SelectValuePrimitive } from 'reka-ui';

interface Props {
    placeholder?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <SelectValuePrimitive :placeholder="placeholder">
        <slot />
    </SelectValuePrimitive>
</template>
