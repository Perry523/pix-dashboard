<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PixDetailsModal from '@/components/PixDetailsModal.vue';
import { useNotifications } from '@/composables/useNotifications';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PixStats, type PixRecord } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CreditCard, Plus, QrCode, TrendingUp, RefreshCw } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

interface Props {
    pixStats: PixStats;
    recentPix: PixRecord[];
}

const props = defineProps<Props>();

const modalOpen = ref(false);
const selectedPix = ref<PixRecord | null>(null);

const stats = ref({
    total: props.pixStats.total,
    paid: props.pixStats.paid,
    expired: props.pixStats.expired,
    generated: props.pixStats.generated,
});

const { fetchUnreadCount } = useNotifications();

const handlePixStatusUpdate = (event: CustomEvent) => {
    const { type } = event.detail;

    if (document.hasFocus() && window.location.pathname === '/dashboard') {
        if (type === 'pix_created') {
            stats.value.total += 1;
            stats.value.generated += 1;
        } else if (type === 'pix_paid') {
            stats.value.generated = Math.max(0, stats.value.generated - 1);
            stats.value.paid += 1;
        } else if (type === 'pix_expired') {
            stats.value.generated = Math.max(0, stats.value.generated - 1);
            stats.value.expired += 1;
        }
        return;
    }

    fetchUnreadCount();
};

const setupPixStatusListener = () => {
    const listener = (event: Event) => {
        handlePixStatusUpdate(event as CustomEvent);
    };

    window.addEventListener('pix-status-update', listener);
};

const handleFocus = () => {
    if (document.hasFocus()) {
        fetchUnreadCount();
    }
};

onMounted(() => {
    window.addEventListener('focus', handleFocus);
    setupPixStatusListener();
});

onUnmounted(() => {
    window.removeEventListener('focus', handleFocus);
    window.removeEventListener('pix-status-update', handlePixStatusUpdate as EventListener);
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'paid':
            return { variant: 'success' as const, label: 'Pago' };
        case 'expired':
            return { variant: 'destructive' as const, label: 'Expirado' };
        default:
            return { variant: 'secondary' as const, label: 'Gerado' };
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('pt-BR');
};

const openPixDetails = (pix: PixRecord) => {
    selectedPix.value = pix;
    modalOpen.value = true;
};

const refreshStats = async () => {
    try {
        const response = await fetch('/api/pix/stats', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Accept': 'application/json',
            }
        });

        if (response.ok) {
            const freshStats = await response.json();
            stats.value = {
                total: freshStats.total,
                paid: freshStats.paid,
                expired: freshStats.expired,
                generated: freshStats.generated,
            };
        }
    } catch (error) {
        console.error('Error refreshing stats:', error);
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6 overflow-x-auto">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Dashboard PIX</h1>
                    <p class="text-muted-foreground">Gerencie seus pagamentos PIX</p>
                </div>
                <div class="flex gap-2">
                    <Button @click="refreshStats" variant="outline">
                        <RefreshCw class="mr-2 h-4 w-4" />
                        Refresh
                    </Button>
                    <Button as-child>
                        <Link href="/pix/generate">
                            <Plus class="mr-2 h-4 w-4" />
                            Gerar PIX
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total PIX</CardTitle>
                        <QrCode class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                        <p class="text-xs text-muted-foreground">Códigos gerados</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">PIX Pagos</CardTitle>
                        <TrendingUp class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.paid }}</div>
                        <p class="text-xs text-muted-foreground">Pagamentos confirmados</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">PIX Gerados</CardTitle>
                        <QrCode class="h-4 w-4 text-yellow-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.generated }}</div>
                        <p class="text-xs text-muted-foreground">Aguardando pagamento</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">PIX Expirados</CardTitle>
                        <CreditCard class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ stats.expired }}</div>
                        <p class="text-xs text-muted-foreground">Códigos vencidos</p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>PIX Recentes</CardTitle>
                    <CardDescription>Últimos códigos PIX gerados</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="recentPix.length === 0" class="text-center py-8 text-muted-foreground">
                        <QrCode class="mx-auto h-12 w-12 mb-4" />
                        <p>Nenhum PIX encontrado</p>
                        <p class="text-sm">Gere seu primeiro código PIX para começar</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="pix in recentPix"
                            :key="pix.id"
                            class="flex items-center justify-between p-4 border rounded-lg"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <code class="text-sm px-2 py-1 rounded">
                                        {{ pix.token.substring(0, 8) }}...
                                    </code>
                                    <Badge :variant="getStatusBadge(pix.status).variant">
                                        {{ getStatusBadge(pix.status).label }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-muted-foreground mt-1">
                                    Criado em {{ formatDate(pix.created_at) }}
                                </p>
                            </div>

                            <Button
                                variant="outline"
                                size="sm"
                                @click="openPixDetails(pix)"
                            >
                                Ver Detalhes
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- PIX Details Modal -->
        <PixDetailsModal
            v-model:open="modalOpen"
            :pix="selectedPix"
        />
    </AppLayout>
</template>
