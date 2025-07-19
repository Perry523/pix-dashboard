<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';

import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import PixDetailsModal from '@/components/PixDetailsModal.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Plus, QrCode, Search, Eye } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import api from '@/lib/axios';

interface PixRecord {
    id: number;
    token: string;
    status: 'generated' | 'paid' | 'expired';
    expires_at: string;
    created_at: string;
}

interface Props {
    pixList?: PixRecord[];
    filters?: {
        search?: string;
        status?: string;
        page?: number;
        per_page?: number;
    };
    total?: number;
}

const props = withDefaults(defineProps<Props>(), {
    pixList: () => [],
    filters: () => ({}),
    total: 0,
});

const searchTerm = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const modalOpen = ref(false);
const selectedPix = ref<PixRecord | null>(null);
const currentPage = ref(props.filters?.page || 1);
const pageSize = ref(props.filters?.per_page || 20);

// No more expiration handling - server handles everything

// Reactive data that will be updated from API
const pixList = ref([...props.pixList]);
const totalItems = ref(props.total || props.pixList.length);
const isLoading = ref(false);

let searchTimeout: number | null = null;

const updateFilters = (immediate = false) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    const doUpdate = () => {
        const params = new URLSearchParams();

        if (searchTerm.value) {
            params.set('search', searchTerm.value);
        }

        if (statusFilter.value !== 'all') {
            params.set('status', statusFilter.value);
        }

        if (currentPage.value > 1) {
            params.set('page', currentPage.value.toString());
        }

        params.set('per_page', pageSize.value.toString());

        isLoading.value = true;

        // Make API request using axios
        api.get('/pix', { params: Object.fromEntries(params) })
            .then(response => {
                // Update the reactive data (server handles expiration)
                pixList.value = response.data.data;
                totalItems.value = response.data.meta.total;

                // Update URL without page reload
                const webUrl = `/pix/list${params.toString() ? '?' + params.toString() : ''}`;
                window.history.pushState({}, '', webUrl);
            })
            .catch(error => {
                console.error('Error fetching PIX data:', error);
            })
            .finally(() => {
                isLoading.value = false;
            });
    };

    if (immediate) {
        doUpdate();
    } else {
        searchTimeout = setTimeout(doUpdate, 500);
    }
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'paid':
            return { variant: 'success' as const, label: 'Pago', color: 'text-green-600' };
        case 'expired':
            return { variant: 'destructive' as const, label: 'Expirado', color: 'text-red-600' };
        default:
            return { variant: 'secondary' as const, label: 'Gerado', color: 'text-yellow-600' };
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('pt-BR');
};



const openPixDetails = (pix: PixRecord) => {
    selectedPix.value = pix;
    modalOpen.value = true;
};

const handleStatusChange = (value: string) => {
    statusFilter.value = value;
    currentPage.value = 1;
    updateFilters(true);
};

const handlePageSizeChange = (value: string | number) => {
    pageSize.value = value === 'all' ? -1 : parseInt(value.toString());
    currentPage.value = 1;
    updateFilters(true);
};

// Watch for search changes with debounce
watch(searchTerm, () => {
    currentPage.value = 1;
    updateFilters();
});

// Watch for current page changes (immediate)
watch(currentPage, () => {
    updateFilters(true);
});

// No cleanup needed - server handles expiration
</script>

<template>

    <Head title="Meus PIX" />

    <AppSidebarLayout :breadcrumbs="[{ title: 'PIX', href: '/pix/list' }, { title: 'Meus PIX', href: '/pix/list' }]">
        <div class="flex flex-col h-full px-4 py-4 overflow-hidden">
            <Card class="flex-1 flex flex-col min-h-0 overflow-hidden">
                <CardHeader class="flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <Search
                                    class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input v-model="searchTerm" placeholder="Buscar por token ou status..."
                                    class="pl-10 w-80" />
                            </div>
                            <select
                                v-model="statusFilter"
                                @change="handleStatusChange(statusFilter)"
                                class="flex h-10 w-40 items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            >
                                <option value="all">Todos</option>
                                <option value="generated">Gerados</option>
                                <option value="paid">Pagos</option>
                                <option value="expired">Expirados</option>
                            </select>
                        </div>
                        <Button as-child>
                            <Link href="/pix/generate">
                            Novo
                            <Plus class="mr-1 h-4 w-3" />
                            </Link>
                        </Button>
                    </div>
                </CardHeader>

                <CardContent class="flex-1 flex flex-col p-0 min-h-0 overflow-hidden">
                    <div v-if="pixList.length === 0" class="flex-1 flex items-center justify-center py-12">
                        <div class="text-center">
                            <QrCode class="mx-auto h-16 w-16 text-muted-foreground mb-4" />
                            <h3 class="text-lg font-medium mb-2">
                                {{ searchTerm || statusFilter !== 'all' ? 'Nenhum PIX encontrado' : 'Nenhum PIX criado ainda' }}
                            </h3>
                            <p class="text-muted-foreground mb-6">
                                {{ searchTerm || statusFilter !== 'all' ? 'Tente ajustar sua busca ou filtro' : 'Crie seu primeiro código PIX para começar' }}
                            </p>
                            <Button v-if="!searchTerm && statusFilter === 'all'" as-child>
                                <Link href="/pix/generate">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Gerar Primeiro PIX
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <div v-else class="flex-1 flex flex-col min-h-0 overflow-hidden">
                        <div v-if="isLoading" class="flex-1 flex items-center justify-center">
                            <div class="text-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
                                <p class="text-muted-foreground">Carregando...</p>
                            </div>
                        </div>
                        <div v-else class="flex-1 min-h-0 overflow-auto">
                            <Table>
                                <TableHeader class="sticky top-0 bg-background z-10">
                                    <TableRow>
                                        <TableHead>Token</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Criado em</TableHead>
                                        <TableHead>Expira em</TableHead>
                                        <TableHead class="text-right">Ações</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="pix in pixList" :key="pix.id">
                                        <TableCell>
                                            <code class="text-sm px-2 py-1 rounded font-mono">
                                                {{ pix.token.substring(0, 12) }}...
                                            </code>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="getStatusBadge(pix.status).variant">
                                                {{ getStatusBadge(pix.status).label }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ formatDate(pix.created_at) }}
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ formatDate(pix.expires_at) }}
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Button variant="outline" size="sm" @click="openPixDetails(pix)">
                                                <Eye class="mr-2 h-4 w-4" />
                                                Detalhes
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <div class="flex items-center flex-shrink-0 border-t p-4">
                            <div class="text-sm text-muted-foreground">
                                {{ totalItems }} {{ totalItems === 1 ? 'código encontrado' : 'códigos encontrados' }}
                            </div>
                            <Pagination
                                v-model:current-page="currentPage"
                                :page-size="pageSize"
                                :total-items="totalItems"
                                :page-size-options="[20, 50, 100]"
                                @update:page-size="handlePageSizeChange"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- PIX Details Modal -->
        <PixDetailsModal v-model:open="modalOpen" :pix="selectedPix" />
    </AppSidebarLayout>
</template>
