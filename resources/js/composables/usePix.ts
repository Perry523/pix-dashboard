import { router } from '@inertiajs/vue3';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { ref, type Ref } from 'vue';
import api from '@/lib/axios';
import type { PixData, PixStats, PixRecord } from '@/types';

interface UsePixGenerationReturn {
    isLoading: Ref<boolean>;
    pixData: Ref<PixData | null>;
    error: Ref<string | null>;
    generatePix: (expiresInMinutes?: number) => Promise<void>;
    copyToClipboard: (text: string) => Promise<boolean>;
    reset: () => void;
}

export function usePixGeneration(): UsePixGenerationReturn {
    const isLoading = ref<boolean>(false);
    const pixData = ref<PixData | null>(null);
    const error = ref<string | null>(null);

    const generatePix = async (expiresInMinutes: number = 10): Promise<void> => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.post<PixData>('/pix', {
                expires_in_minutes: expiresInMinutes
            });
            pixData.value = response.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Erro desconhecido';
        } finally {
            isLoading.value = false;
        }
    };

    const copyToClipboard = async (text: string): Promise<boolean> => {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            return false;
        }
    };

    const reset = (): void => {
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

interface UsePixConfirmationReturn {
    confirmPix: (token: string) => void;
}

export function usePixConfirmation(): UsePixConfirmationReturn {
    const confirmPix = (token: string): void => {
        router.visit(route('pix.confirm', token));
    };

    return {
        confirmPix,
    };
}

interface UsePixValidationReturn {
    pixGenerationSchema: ReturnType<typeof toTypedSchema>;
}

export function usePixValidation(): UsePixValidationReturn {
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
