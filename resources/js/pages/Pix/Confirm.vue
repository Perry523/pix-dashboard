<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, Clock, XCircle, ArrowLeft, Home } from 'lucide-vue-next';
import { computed } from 'vue';

interface PixData {
    id: number;
    token: string;
    status: 'generated' | 'paid' | 'expired';
    expires_at: string;
    created_at: string;
    user: {
        name: string;
        email: string;
    };
}

interface Props {
    pix: PixData;
}

const props = defineProps<Props>();

const statusConfig = computed(() => {
    switch (props.pix.status) {
        case 'paid':
            return {
                icon: CheckCircle,
                color: 'bg-green-100 text-green-800',
                title: 'Pagamento Confirmado',
                description: 'Este PIX foi pago com sucesso!',
                variant: 'success' as const,
            };
        case 'expired':
            return {
                icon: XCircle,
                color: 'bg-red-100 text-red-800',
                title: 'PIX Expirado',
                description: 'Este código PIX expirou e não pode mais ser usado.',
                variant: 'destructive' as const,
            };
        default:
            return {
                icon: Clock,
                color: 'bg-yellow-100 text-yellow-800',
                title: 'Processando Pagamento',
                description: 'Confirmando o pagamento deste PIX...',
                variant: 'secondary' as const,
            };
    }
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('pt-BR');
};
</script>

<template>
    <Head title="Confirmação PIX" />
    
    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <Card>
                <CardHeader class="text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full" :class="statusConfig.color">
                        <component :is="statusConfig.icon" class="h-8 w-8" />
                    </div>
                    
                    <CardTitle class="text-xl">{{ statusConfig.title }}</CardTitle>
                    <CardDescription>{{ statusConfig.description }}</CardDescription>
                </CardHeader>
                
                <CardContent class="space-y-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500">Status</span>
                            <Badge :variant="statusConfig.variant">
                                {{ pix.status === 'paid' ? 'Pago' : pix.status === 'expired' ? 'Expirado' : 'Gerado' }}
                            </Badge>
                        </div>
                        
                        <Separator />
                        
                        <div class="space-y-2">
                            <!-- <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Token</span>
                                <span class="text-sm font-mono">{{ pix.token.substring(0, 8) }}...</span>
                            </div> -->
                            
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Criado em</span>
                                <span class="text-sm">{{ formatDate(pix.created_at) }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Expira em</span>
                                <span class="text-sm">{{ formatDate(pix.expires_at) }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Beneficiário</span>
                                <span class="text-sm">{{ pix?.user?.name }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <Separator />
                    
                    <div class="space-y-3">
                        <div v-if="pix.status === 'paid'" class="rounded-md bg-green-50 p-4">
                            <div class="flex items-center gap-2 text-green-800">
                                <CheckCircle class="h-5 w-5" />
                                <div>
                                    <p class="font-medium">Pagamento realizado!</p>
                                    <p class="text-sm">O PIX foi confirmado com sucesso.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else-if="pix.status === 'expired'" class="rounded-md bg-red-50 p-4">
                            <div class="flex items-center gap-2 text-red-800">
                                <XCircle class="h-5 w-5" />
                                <div>
                                    <p class="font-medium">PIX expirado</p>
                                    <p class="text-sm">Este código não pode mais ser usado para pagamento.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="rounded-md bg-blue-50 p-4">
                            <div class="flex items-center gap-2 text-blue-800">
                                <Clock class="h-5 w-5" />
                                <div>
                                    <p class="font-medium">Pagamento processado</p>
                                    <p class="text-sm">O status foi atualizado para pago automaticamente.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div v-if="pix.status === 'paid'" class="text-center">
                            <p class="text-lg font-medium text-green-600 mb-2">Pagamento Confirmado!</p>
                            <p class="text-sm text-gray-600">Obrigado por utilizar nosso sistema PIX.</p>
                        </div>

                        <div v-else-if="pix.status === 'expired'" class="text-center">
                            <p class="text-lg font-medium text-red-600 mb-2">PIX Expirado</p>
                            <p class="text-sm text-gray-600">Este código PIX não é mais válido.</p>
                        </div>

                        <div v-else class="text-center">
                            <p class="text-lg font-medium text-blue-600 mb-2">Processando Pagamento</p>
                            <p class="text-sm text-gray-600">Seu pagamento foi processado com sucesso!</p>
                        </div>

                        <Button class="w-full" as-child>
                            <Link href="/">
                                <Home class="mr-2 h-4 w-4" />
                                Voltar ao Início
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
