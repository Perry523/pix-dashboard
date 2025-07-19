<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head } from '@inertiajs/vue3';
import { CheckCircle, Copy, Timer, Smartphone, CreditCard } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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

const copied = ref(false);

const confirmationLink = computed(() => {
    return `${window.location.origin}/pix/${props.pix.token}`;
});

const handleCopyLink = async () => {
    try {
        await navigator.clipboard.writeText(confirmationLink.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (error) {
        console.error('Erro ao copiar link:', error);
    }
};

const handleShare = async () => {
    if (navigator.share) {
        try {
            await navigator.share({
                title: 'Pagamento PIX',
                text: `Realize o pagamento PIX para ${props.pix.user.name}`,
                url: confirmationLink.value,
            });
        } catch (error) {
            console.error('Erro ao compartilhar:', error);
        }
    } else {
        handleCopyLink();
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('pt-BR');
};

const handlePayment = () => {
    window.location.href = confirmationLink.value;
};
</script>

<template>
    <Head title="Pagamento PIX" />
    
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <Card class="shadow-xl">
                <!-- <CardHeader class="text-center bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-t-lg">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white/20">
                        <QrCode class="h-8 w-8" />
                    </div>
                    
                    <CardTitle class="text-xl">Pagamento PIX</CardTitle>
                    <CardDescription class="text-blue-100">
                        Escaneie o QR Code ou clique para pagar
                    </CardDescription>
                </CardHeader> -->
                
                <CardContent class="space-y-6 p-6">
                    <!-- Status Check -->
                    <div v-if="pix.status !== 'generated'" class="text-center">
                        <div v-if="pix.status === 'paid'" class="text-green-600">
                            <CheckCircle class="mx-auto h-12 w-12 mb-2" />
                            <p class="font-medium">Pagamento realizado!</p>
                        </div>
                        <div v-else class="text-red-600">
                            <CreditCard class="mx-auto h-12 w-12 mb-2" />
                            <p class="font-medium">PIX expirado</p>
                        </div>
                        <Button @click="() => window.close()" class="mt-4">
                            Voltar ao Início
                        </Button>
                    </div>

                    <!-- Active PIX -->
                    <div v-else class="space-y-6">
                        <!-- QR Code -->
                        <div class="text-center">
                            <div class="flex justify-center mb-4">
                                <div class="p-4 bg-white border-2 border-gray-200 rounded-xl shadow-sm">
                                    <img 
                                        :src="`/pix/${pix.token}/qrcode`" 
                                        alt="QR Code PIX"
                                        class="w-48 h-48"
                                    />
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-center gap-2 text-sm text-gray-600">
                                <Smartphone class="h-4 w-4" />
                                <span>Abra seu app de banco e escaneie o código</span>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <div class="flex-1 border-t border-gray-200"></div>
                            <span class="px-3 text-sm text-gray-500">ou</span>
                            <div class="flex-1 border-t border-gray-200"></div>
                        </div>

                        <!-- Informações do Pagamento -->
                        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Beneficiário:</span>
                                <span class="font-medium">{{ pix.user.name }}</span>
                            </div>
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Criado em:</span>
                                <span>{{ formatDate(pix.created_at) }}</span>
                            </div>
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Válido até:</span>
                                <span>{{ formatDate(pix.expires_at) }}</span>
                            </div>
                        </div>

                        <!-- Expiration Info -->
                        <div class="flex items-center justify-center gap-2 text-sm bg-blue-50 text-blue-800 p-3 rounded-lg">
                            <Timer class="h-4 w-4" />
                            <span>Expira em: {{ formatDate(pix.expires_at) }}</span>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="space-y-3">
                            <Button 
                                @click="handlePayment" 
                                class="w-full"
                                size="lg"
                            >
                                <CreditCard class="mr-2 h-4 w-4" />
                                Confirmar Pagamento
                            </Button>
                            
                            <Button 
                                @click="handleShare" 
                                variant="outline" 
                                class="w-full"
                            >
                                <Copy class="mr-2 h-4 w-4" />
                                Compartilhar Link
                            </Button>
                        </div>

                        <!-- Instruções -->
                        <div class="text-xs text-gray-500 text-center space-y-1">
                            <p>• Escaneie o QR Code com seu app bancário</p>
                            <p>• Ou clique em "Confirmar Pagamento"</p>
                            <p>• O pagamento expira em 10 minutos</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
