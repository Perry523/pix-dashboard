<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    variant?: 'default' | 'secondary' | 'destructive' | 'success' | 'outline';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
});

const getVariantClasses = (variant: string) => {
    const baseClasses = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2';

    switch (variant) {
        case 'secondary':
            return `${baseClasses} border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80`;
        case 'destructive':
            return `${baseClasses} border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80`;
        case 'success':
            return `${baseClasses} border-transparent bg-green-500 text-white hover:bg-green-600`;
        case 'outline':
            return `${baseClasses} text-foreground`;
        default:
            return `${baseClasses} border-transparent bg-primary text-primary-foreground hover:bg-primary/80`;
    }
};
</script>

<template>
    <div :class="cn(getVariantClasses(props.variant), props.class)">
        <slot />
    </div>
</template>
