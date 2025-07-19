import { ref, type Ref } from 'vue';
import * as PusherPushNotifications from '@pusher/push-notifications-web';
import { usePage } from '@inertiajs/vue3';
import type { User } from '@/types';

const beamsClient = ref<PusherPushNotifications.Client | null>(null);
const isInitialized = ref<boolean>(false);

interface UsePusherBeamsReturn {
    beamsClient: Ref<PusherPushNotifications.Client | null>;
    isInitialized: Ref<boolean>;
    initializePusherBeams: () => Promise<boolean>;
    clearPusherBeams: () => Promise<void>;
    requestNotificationPermission: () => Promise<boolean>;
}

export function usePusherBeams(): UsePusherBeamsReturn {
    const initializePusherBeams = async (): Promise<boolean> => {
        const page = usePage();
        const user = page.props.auth.user as User;
        try {
            const instanceId = import.meta.env.VITE_PUSHER_BEAMS_INSTANCE_ID;

            if (!instanceId) {
                console.warn('Pusher Beams instance ID not configured');
                return false;
            }

            if (!beamsClient.value) {
                beamsClient.value = new PusherPushNotifications.Client({
                    instanceId: instanceId,
                });
            }

            await beamsClient.value.start();
            await beamsClient.value.addDeviceInterest(`${user.id}`);

            isInitialized.value = true;
            return true;
        } catch (error) {
            console.error('Failed to initialize Pusher Beams:', error);
            isInitialized.value = false;
            return false;
        }
    };

    const clearPusherBeams = async (): Promise<void> => {
        try {
            if (beamsClient.value && isInitialized.value) {
                await beamsClient.value.clearAllState();
                isInitialized.value = false;
            }
        } catch (error) {
            console.error('Failed to clear Pusher Beams:', error);
        }
    };

    const requestNotificationPermission = async (): Promise<boolean> => {
        if ('Notification' in window) {
            const permission = await Notification.requestPermission();
            return permission === 'granted';
        }
        return false;
    };

    return {
        beamsClient,
        isInitialized,
        initializePusherBeams,
        clearPusherBeams,
        requestNotificationPermission,
    };
}
