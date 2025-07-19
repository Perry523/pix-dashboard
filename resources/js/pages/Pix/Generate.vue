<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { usePixGeneration, usePixValidation } from '@/composables/usePix';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, Copy, LoaderCircle, QrCode, Timer } from 'lucide-vue-next';
import { ref } from 'vue';
import { useForm } from 'vee-validate';

const { isLoading, pixData, error, generatePix, copyToClipboard, reset } = usePixGeneration();
const { pixGenerationSchema } = usePixValidation();

const { handleSubmit, defineField, errors } = useForm({
    validationSchema: pixGenerationSchema,
    initialValues: {
        expires_in_minutes: 10
    }
});

const [expiresInMinutes] = defineField('expires_in_minutes');
const copied = ref(false);

// Removed static countdown - server handles expiration with notifications

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('pt-BR');
};

const handleGeneratePix = handleSubmit(async (values) => {
    await generatePix(values.expires_in_minutes);
});

// Removed unused handleCopyToken function

const handleCopyUrl = async () => {
    if (!pixData.value) return;

    await copyToClipboard(pixData.value.payment_link);
};

const handleReset = () => {
    reset();
    copied.value = false;
};
</script>

<template>

    <Head title="Gerar PIX" />

    <AppSidebarLayout :breadcrumbs="[{ title: 'PIX', href: '/pix/list' }, { title: 'Gerar PIX', href: '/pix/generate' }]">
        <div class="container mx-auto max-w-2xl py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold">Gerar PIX</h1>
                <p class="text-muted-foreground mt-2">
                    Crie um novo código PIX para receber pagamentos
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <QrCode class="h-5 w-5" />
                        Novo PIX
                    </CardTitle>
                    <CardDescription>
                        Configure o tempo de expiração e gere um novo código PIX
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <div v-if="error" class="rounded-md bg-red-50 p-4 text-red-800">
                        {{ error }}
                    </div>

                    <div v-if="!pixData" class="space-y-4">
                        <div>
                            <Label for="expires_in_minutes">Tempo de Expiração (minutos)</Label>
                            <Input
                                id="expires_in_minutes"
                                v-model="expiresInMinutes"
                                type="number"
                                min="1"
                                max="1440"
                                placeholder="10"
                                class="mt-1"
                            />
                            <p v-if="errors.expires_in_minutes" class="text-sm text-red-600 mt-1">
                                {{ errors.expires_in_minutes }}
                            </p>
                            <p class="text-xs text-muted-foreground mt-1">
                                Mínimo: 1 minuto | Máximo: 1440 minutos (24 horas)
                            </p>
                        </div>

                        <div class="text-center">
                            <Button @click="handleGeneratePix" :disabled="isLoading" size="lg" class="w-full">
                                <LoaderCircle v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                                <QrCode v-else class="mr-2 h-4 w-4" />
                                {{ isLoading ? 'Gerando...' : 'Gerar PIX' }}
                            </Button>
                        </div>
                    </div>

                    <div v-else class="space-y-6">
                        <div class="rounded-md bg-green-50 p-4">
                            <div class="flex items-center gap-2 text-green-800">
                                <CheckCircle class="h-5 w-5" />
                                <span class="font-medium">PIX gerado com sucesso!</span>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <!-- QR Code Section -->
                            <div class="text-center">
                                <Label class="text-base font-medium">QR Code para Pagamento</Label>
                                <div class="mt-3 flex justify-center">
                                    <div class="p-4 bg-white border-2 border-gray-200 rounded-lg inline-block">
                                        <img :src="`/pix/${pixData.token}/qrcode`" alt="QR Code PIX"
                                            class="w-48 h-48" />
                                    </div>
                                </div>
                                <p class="text-sm text-muted-foreground mt-2">
                                    Escaneie este QR Code para realizar o pagamento
                                </p>
                            </div>

                            <Separator />


                            <div>

                                <Label for="url">Link de Pagamento</Label>
                                <div class="flex gap-2 mt-1">
                                    <Input id="url" v-model="pixData.payment_link" readonly class="text-sm" />
                                    <Button @click="handleCopyUrl" variant="outline" size="icon">
                                        <Copy class="h-4 w-4" />
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground mt-1">
                                    Compartilhe este link com o cliente para pagamento
                                </p>
                            </div>

                            <!-- Expiration Info -->
                            <div
                                class="flex items-center gap-2 text-sm text-muted-foreground bg-blue-50 p-3 rounded-lg">
                                <Timer class="h-4 w-4" />
                                <span>Expira em: {{ formatDate(pixData.expires_at) }}</span>
                            </div>
                        </div>

                        <Separator />

                        <div class="flex gap-2">
                            <Button @click="handleReset" variant="outline" class="flex-1">
                                Gerar Novo PIX
                            </Button>
                            <a :href="pixData.payment_link" target="_blank" class="flex-1">
                                <Button class="w-full">
                                    Testar Pagamento
                                </Button>
                            </a>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppSidebarLayout>
</template>
