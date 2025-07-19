<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { CheckCircle, Copy, QrCode, Share2, ExternalLink } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface PixRecord {
    id: number;
    token: string;
    status: 'generated' | 'paid' | 'expired';
    expires_at: string;
    created_at: string;
}

interface Props {
    open: boolean;
    pix: PixRecord | null;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const copied = ref<'token' | 'link' | null>(null);

const shareLink = computed(() => {
    if (!props.pix) return '';
    return `${window.location.origin}/pix/${props.pix.token}`;
});

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

const handleCopy = async (type: 'token' | 'link', text: string) => {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = type;
        setTimeout(() => {
            copied.value = null;
        }, 2000);
    } catch (error) {
        console.error('Erro ao copiar:', error);
    }
};

const handleShare = async () => {
    if (!props.pix) return;

    if (navigator.share) {
        try {
            await navigator.share({
                title: 'Pagamento PIX',
                text: 'Realize o pagamento através deste link PIX',
                url: shareLink.value,
            });
        } catch (error) {
            console.error('Erro ao compartilhar:', error);
        }
    } else {
        handleCopy('link', shareLink.value);
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('pt-BR');
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <QrCode class="h-5 w-5" />
                    Detalhes do PIX
                </DialogTitle>
                <DialogDescription>
                    Compartilhe o QR Code ou link com o cliente
                </DialogDescription>
            </DialogHeader>

            <div v-if="pix" class="space-y-6">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Status:</span>
                    <Badge :variant="getStatusBadge(pix.status).variant">
                        {{ getStatusBadge(pix.status).label }}
                    </Badge>
                </div>

                <div v-if="pix.status === 'generated'" class="text-center">
                    <Label class="text-sm font-medium">QR Code para Pagamento</Label>
                    <div class="mt-3 flex justify-center">
                        <div class="p-3 bg-white border-2 border-gray-200 rounded-lg inline-block">
                            <img :src="`/pix/${pix.token}/qrcode`" alt="QR Code PIX" class="w-32 h-32" />
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground mt-2">
                        Cliente deve escanear este código
                    </p>
                </div>

                <Separator />

                <!-- Link de Compartilhamento -->
                <div v-if="pix.status === 'generated'">
                    <Label for="modal-share-link" class="text-sm font-medium">Link para Cliente</Label>
                    <div class="flex gap-2 mt-1">
                        <Input id="modal-share-link" v-model="shareLink" readonly class="text-xs" />
                        <Button @click="handleCopy('link', shareLink)" variant="outline" size="icon"
                            :class="{ 'bg-green-50 text-green-600': copied === 'link' }">
                            <CheckCircle v-if="copied === 'link'" class="h-4 w-4" />
                            <Copy v-else class="h-4 w-4" />
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        Envie este link para o cliente
                    </p>
                </div>

                <!-- Informações -->
                <div class=" p-3 rounded-lg space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="">Criado:</span>
                        <span>{{ formatDate(pix.created_at) }}</span>
                    </div>
                    <div v-if="pix.status === 'generated'" class="flex justify-between">
                        <span class="">Expira:</span>
                        <span>{{ formatDate(pix.expires_at) }}</span>
                    </div>
                    <!-- Removed countdown - server handles expiration -->
                </div>

                <!-- Ações -->
                <div v-if="pix.status === 'generated'" class="space-y-2">
                    <Button @click="handleShare" class="w-full" :disabled="pix.status !== 'generated'">
                        <Share2 class="mr-2 h-4 w-4" />
                        Compartilhar com Cliente
                    </Button>
                    <a :href="shareLink" target="_blank">
                        <Button variant="outline" class="w-full" :disabled="pix.status !== 'generated'">
                            <ExternalLink class="mr-2 h-4 w-4" />
                            Abrir Página de Pagamento
                        </Button>
                    </a>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
