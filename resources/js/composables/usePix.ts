import { router } from '@inertiajs/vue3';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { ref } from 'vue';
import api from '@/lib/axios';

export interface PixData {
    token: string;
    expires_at: string;
    payment_link: string;
    qr_code_svg: string;
    qr_code_base64: string;
    status: string;
}

export interface PixStats {
    generated: number;
    paid: number;
    expired: number;
    total: number;
}

export interface PixRecord {
    id: number;
    token: string;
    status: 'generated' | 'paid' | 'expired';
    expires_at: string;
    created_at: string;
}

export function usePixGeneration() {
    const isLoading = ref(false);
    const pixData = ref<PixData | null>(null);
    const error = ref<string | null>(null);

    const generatePix = async (expiresInMinutes: number = 10) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.post('/pix', {
                expires_in_minutes: expiresInMinutes
            });
            pixData.value = response.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Erro desconhecido';
        } finally {
            isLoading.value = false;
        }
    };

    const copyToClipboard = async (text: string) => {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            return false;
        }
    };

    const reset = () => {
        pixData.value = null;
        error.value = null;
        isLoading.value = false;
    };

    return {
        isLoading,
        pixData,
        error,
        generatePix,
        copyToClipboard,
        reset,
    };
}

export function usePixConfirmation() {
    const confirmPix = (token: string) => {
        router.visit(route('pix.confirm', token));
    };

    return {
        confirmPix,
    };
}

export function usePixValidation() {
    const pixGenerationSchema = toTypedSchema(
        yup.object({
            expires_in_minutes: yup
                .number()
                .min(1, 'Mínimo de 1 minuto')
                .max(1440, 'Máximo de 24 horas (1440 minutos)')
                .required('Campo obrigatório')
        })
    );

    return {
        pixGenerationSchema,
    };
}


