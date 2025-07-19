import { ref, onMounted, onUnmounted } from 'vue';
import api from '@/lib/axios';
import { usePusherBeams } from '@/composables/usePusherBeams';

export interface Notification {
    id: number;
    type: string;
    title: string;
    message: string;
    data: any;
    read_at: string | null;
    created_at: string;
}

export function useNotifications() {
    const notifications = ref<Notification[]>([]);
    const unreadCount = ref(0);
    const isLoading = ref(false);

    // Use shared Pusher Beams instance
    const { beamsClient, isInitialized } = usePusherBeams();

    // Fetch notifications from API
    const fetchNotifications = async (page = 1, perPage = 20) => {
        isLoading.value = true;
        try {
            const response = await api.get('/notifications', {
                params: { page, per_page: perPage }
            });
            
            notifications.value = response.data.data;
            return response.data;
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
            return null;
        } finally {
            isLoading.value = false;
        }
    };

    // Fetch unread count
    const fetchUnreadCount = async () => {
        try {
            const response = await api.get('/notifications/unread-count');
            unreadCount.value = response.data.count;
        } catch (error) {
            console.error('Failed to fetch unread count:', error);
        }
    };

    // Mark notification as read
    const markAsRead = async (notificationId: number) => {
        try {
            await api.post(`/notifications/${notificationId}/read`);
            
            // Update local state
            const notification = notifications.value.find(n => n.id === notificationId);
            if (notification) {
                notification.read_at = new Date().toISOString();
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    };

    // Mark all notifications as read
    const markAllAsRead = async () => {
        try {
            await api.post('/notifications/mark-all-read');
            
            // Update local state
            notifications.value.forEach(notification => {
                if (!notification.read_at) {
                    notification.read_at = new Date().toISOString();
                }
            });
            unreadCount.value = 0;
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    };

    // Handle incoming push notifications
    const handlePushNotification = (payload: any) => {
        console.log('Received push notification:', payload);
        
        // Update unread count
        unreadCount.value += 1;
        
        // If we're on the dashboard and it's in focus, refresh notifications
        if (document.hasFocus() && window.location.pathname === '/dashboard') {
            fetchNotifications();
        }
        
        // Show browser notification if page is not in focus
        if (!document.hasFocus()) {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(payload.title, {
                    body: payload.body,
                    icon: payload.icon || '/favicon.ico',
                    badge: payload.badge || '/favicon.ico',
                });
            }
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

    // Initialize everything
    const initialize = async () => {
        await fetchUnreadCount();
    };

    // Auto-initialize on mount
    onMounted(initialize);

    return {
        notifications,
        unreadCount,
        isLoading,
        fetchNotifications,
        fetchUnreadCount,
        markAsRead,
        markAllAsRead,
        initialize,
    };
}
