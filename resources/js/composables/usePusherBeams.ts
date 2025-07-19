import { ref } from 'vue';
import * as PusherPushNotifications from '@pusher/push-notifications-web';
import { usePage } from '@inertiajs/vue3';
import { User } from '@/types';

const beamsClient = ref<any>(null);
const isInitialized = ref(false);

export function usePusherBeams() {
    // Initialize Pusher Beams (call this on login)
    const initializePusherBeams = async () => {
        const page = usePage();
        const user = page.props.auth.user as User;
        try {
            // Get Pusher Beams instance ID from environment
            const instanceId = import.meta.env.VITE_PUSHER_BEAMS_INSTANCE_ID;
            
            if (!instanceId) {
                console.warn('Pusher Beams instance ID not configured');
                return false;
            }

            // Create client if not exists
            if (!beamsClient.value) {
                beamsClient.value = new PusherPushNotifications.Client({
                    instanceId: instanceId,
                });
            }

            // Start Pusher Beams
            await beamsClient.value.start();

            // Subscribe to user interest
            await beamsClient.value.addDeviceInterest(`${user.id}`);

            isInitialized.value = true;
            console.log('Pusher Beams initialized successfully with interest:', user.id);
            
            return true;
        } catch (error) {
            console.error('Failed to initialize Pusher Beams:', error);
            isInitialized.value = false;
            return false;
        }
    };

    // Clear interests on logout
    const clearPusherBeams = async () => {
        try {
            if (beamsClient.value && isInitialized.value) {
                await beamsClient.value.clearAllState();
                isInitialized.value = false;
                console.log('Pusher Beams cleared successfully');
            }
        } catch (error) {
            console.error('Failed to clear Pusher Beams:', error);
        }
    };

    // Request notification permission
    const requestNotificationPermission = async () => {
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
