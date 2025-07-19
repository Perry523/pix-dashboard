import { usePage } from '@inertiajs/vue3';
import { setAuthToken, removeAuthToken } from '@/lib/axios';
import { usePusherBeams } from '@/composables/usePusherBeams';
import { computed, watch } from 'vue';

export function useAuth() {
    const page = usePage();
    const { clearPusherBeams, requestNotificationPermission } = usePusherBeams();

    const user = computed(() => page.props.auth?.user);
    const token = computed(() => localStorage.getItem('auth_token'));

    const initializeAuth = async () => {
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

    // Watch for user changes (login/logout)
    watch(user, async (newUser) => {
        if (newUser && token.value) {
            // User logged in - initialize Pusher Beams
            await requestNotificationPermission();
        } else if (!newUser) {
            // User logged out - clear Pusher Beams
            await clearPusherBeams();
        }
    }, { immediate: true });
    
    return {
        user,
        token,
        initializeAuth,
    };
}
