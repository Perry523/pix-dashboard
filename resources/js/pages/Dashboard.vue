<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PixDetailsModal from '@/components/PixDetailsModal.vue';
import { useNotifications } from '@/composables/useNotifications';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CreditCard, Plus, QrCode, TrendingUp, RefreshCw } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

interface PixStats {
    generated: number;
    paid: number;
    expired: number;
    total: number;
}

interface PixRecord {
    id: number;
    token: string;
    status: 'generated' | 'paid' | 'expired';
    expires_at: string;
    created_at: string;
}

interface Props {
    pixStats: PixStats;
    recentPix: PixRecord[];
}

const props = defineProps<Props>();

const modalOpen = ref(false);
const selectedPix = ref<PixRecord | null>(null);

// Reactive stats that can be updated by push notifications
const stats = ref({
    total: props.pixStats.total,
    paid: props.pixStats.paid,
    expired: props.pixStats.expired,
    generated: props.pixStats.generated,
});

// Initialize notifications
const { fetchUnreadCount } = useNotifications();

// Handle PIX status updates to update dashboard stats
const handlePixStatusUpdate = (event: CustomEvent) => {
    console.log('🔔 Dashboard received PIX status update:', event.detail);

    // Only update stats if dashboard is in focus (visible)
    if (document.hasFocus() && window.location.pathname === '/dashboard') {
        console.log('📊 Dashboard is in focus, updating stats directly');

        const { type } = event.detail;
        console.log('📝 Update type:', type);

        if (type === 'pix_paid') {
            // Decrease generated count, increase paid count
            const oldStats = { ...stats.value };
            stats.value.generated = Math.max(0, stats.value.generated - 1);
            stats.value.paid += 1;

            console.log('💰 Updated stats for PIX payment:', {
                before: oldStats,
                after: stats.value
            });
        } else if (type === 'pix_expired') {
            // Decrease generated count, increase expired count
            const oldStats = { ...stats.value };
            stats.value.generated = Math.max(0, stats.value.generated - 1);
            stats.value.expired += 1;

            console.log('⏰ Updated stats for PIX expiration:', {
                before: oldStats,
                after: stats.value
            });
        }

        // Don't show browser notification since we're updating the UI directly
        return;
    }

    console.log('🔕 Dashboard not in focus, using normal notification flow');
    // If dashboard is not in focus, let the normal notification flow handle it
    fetchUnreadCount();
};

// Set up custom event listener for PIX status updates
const setupPixStatusListener = () => {
    // Listen for custom events
    const listener = (event: Event) => {
        console.log('🎯 Dashboard received pix-status-update event:', event);
        handlePixStatusUpdate(event as CustomEvent);
    };

    window.addEventListener('pix-status-update', listener);

    // Also listen directly to service worker messages as backup
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            console.log('🔧 Dashboard received service worker message:', event.data);

            if (event.data && event.data.type === 'PUSH_NOTIFICATION') {
                const payload = event.data.payload;
                console.log('🔧 Processing push notification directly in Dashboard:', payload);

                // Try to determine notification type
                let notificationType = null;

                if (payload.data?.type) {
                    notificationType = payload.data.type;
                } else if (payload.notification?.title) {
                    const title = payload.notification.title.toLowerCase();
                    if (title.includes('pago') || title.includes('paid')) {
                        notificationType = 'pix_paid';
                    } else if (title.includes('expirado') || title.includes('expired')) {
                        notificationType = 'pix_expired';
                    }
                }

                console.log('🔧 Direct detection - notification type:', notificationType);

                if (notificationType === 'pix_paid' || notificationType === 'pix_expired') {
                    // Create synthetic custom event
                    const syntheticEvent = new CustomEvent('pix-status-update', {
                        detail: {
                            type: notificationType,
                            payload: payload
                        }
                    });

                    handlePixStatusUpdate(syntheticEvent);
                }
            }
        });
    }

    console.log('✅ Dashboard PIX status listener set up (with service worker backup)');
};

// Handle page focus to refresh data if needed
const handleFocus = () => {
    if (document.hasFocus()) {
        fetchUnreadCount();
    }
};

onMounted(() => {
    window.addEventListener('focus', handleFocus);

    // Set up PIX status listener
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
    console.log('🔄 Manual refresh triggered');
    try {
        // Fetch fresh stats from API
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
            console.log('✅ Stats refreshed:', stats.value);
        } else {
            console.error('❌ Failed to refresh stats:', response.status);
        }
    } catch (error) {
        console.error('❌ Error refreshing stats:', error);
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
