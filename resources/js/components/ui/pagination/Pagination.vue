<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    currentPage: number;
    totalItems: number;
    pageSize: number;
    pageSizeOptions?: number[];
}

interface Emits {
    (e: 'update:currentPage', value: number): void;
    (e: 'update:pageSize', value: number): void;
}

const props = withDefaults(defineProps<Props>(), {
    pageSizeOptions: () => [20, 50, 100],
});

const emit = defineEmits<Emits>();

const totalPages = computed(() => {
    if (props.pageSize === -1) return 1; // "All" option
    return Math.ceil(props.totalItems / props.pageSize);
});

const startItem = computed(() => {
    if (props.pageSize === -1) return 1;
    return (props.currentPage - 1) * props.pageSize + 1;
});

const endItem = computed(() => {
    if (props.pageSize === -1) return props.totalItems;
    return Math.min(props.currentPage * props.pageSize, props.totalItems);
});

const canGoPrevious = computed(() => props.currentPage > 1);
const canGoNext = computed(() => props.currentPage < totalPages.value);

const visiblePages = computed(() => {
    const pages = [];
    const maxVisible = 5;
    const half = Math.floor(maxVisible / 2);
    
    let start = Math.max(1, props.currentPage - half);
    let end = Math.min(totalPages.value, start + maxVisible - 1);
    
    if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1);
    }
    
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    
    return pages;
});

const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        emit('update:currentPage', page);
    }
};

const changePageSize = (size: string) => {
    const newSize = size === 'all' ? -1 : parseInt(size);
    emit('update:pageSize', newSize);
    emit('update:currentPage', 1);
};
</script>

<template>
    <div class="flex items-center justify-between px-2 w-full">
        <div class="flex items-center space-x-6 lg:space-x-8">
            <div class="flex items-center space-x-2">
                <p class="text-sm font-medium">Itens por página</p>
                <select
                    :value="pageSize === -1 ? 'all' : pageSize.toString()"
                    @change="changePageSize(($event.target as HTMLSelectElement).value)"
                    class="h-8 w-[70px] rounded-md border border-input bg-background px-2 py-1 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                >
                    <option v-for="size in pageSizeOptions" :key="size" :value="size.toString()">
                        {{ size }}
                    </option>
                    <option value="all">Todos</option>
                </select>
            </div>
            
            <div class="flex w-[100px] items-center justify-center text-sm font-medium">
                {{ startItem }}-{{ endItem }} de {{ totalItems }}
            </div>
        </div>
        
        <div v-if="pageSize !== -1" class="flex items-center space-x-2 ml-auto">
            <Button
                variant="outline"
                size="sm"
                :disabled="!canGoPrevious"
                @click="goToPage(currentPage - 1)"
            >
                <ChevronLeft class="h-4 w-4" />
            </Button>
            
            <div class="flex items-center space-x-1">
                <Button
                    v-for="page in visiblePages"
                    :key="page"
                    variant="outline"
                    size="sm"
                    :class="{ 'font-black': page === currentPage }"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>
            </div>
            
            <Button
                variant="outline"
                size="sm"
                :disabled="!canGoNext"
                @click="goToPage(currentPage + 1)"
            >
                <ChevronRight class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
