import { usePage } from '@inertiajs/vue3';
import { setAuthToken, removeAuthToken } from '@/lib/axios';
import { usePusherBeams } from '@/composables/usePusherBeams';
import { computed, watch, type ComputedRef } from 'vue';
import type { User } from '@/types';

interface UseAuthReturn {
    user: ComputedRef<User | undefined>;
    token: ComputedRef<string | null>;
    initializeAuth: () => Promise<void>;
}

export function useAuth(): UseAuthReturn {
    const page = usePage();
    const { clearPusherBeams, requestNotificationPermission } = usePusherBeams();

    const user = computed(() => page.props.auth?.user);
    const token = computed(() => localStorage.getItem('auth_token'));

    const initializeAuth = async (): Promise<void> => {
        if (token.value) {
            setAuthToken(token.value);

            if (user.value) {
                await requestNotificationPermission();
            }
        } else {
            removeAuthToken();
            await clearPusherBeams();
        }
    };

    watch(user, async (newUser) => {
        if (newUser && token.value) {
            await requestNotificationPermission();
        } else if (!newUser) {
            await clearPusherBeams();
        }
    }, { immediate: true });

    return {
        user,
        token,
        initializeAuth,
    };
}
