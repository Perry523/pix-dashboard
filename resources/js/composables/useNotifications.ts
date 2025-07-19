import { ref, onMounted, type Ref } from 'vue';
import api from '@/lib/axios';
import { usePusherBeams } from '@/composables/usePusherBeams';
import type { NotificationData, PaginatedResponse } from '@/types';

export interface Notification {
    id: number;
    type: string;
    title: string;
    message: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
}

interface UseNotificationsReturn {
    notifications: Ref<Notification[]>;
    unreadCount: Ref<number>;
    isLoading: Ref<boolean>;
    fetchNotifications: (page?: number, perPage?: number) => Promise<PaginatedResponse<Notification> | null>;
    fetchUnreadCount: () => Promise<void>;
    markAsRead: (notificationId: number) => Promise<void>;
    markAllAsRead: () => Promise<void>;
    initialize: () => Promise<void>;
}

export function useNotifications(): UseNotificationsReturn {
    const notifications = ref<Notification[]>([]);
    const unreadCount = ref<number>(0);
    const isLoading = ref<boolean>(false);

    const { beamsClient, isInitialized } = usePusherBeams();

    const fetchNotifications = async (page: number = 1, perPage: number = 20): Promise<PaginatedResponse<Notification> | null> => {
        isLoading.value = true;
        try {
            const response = await api.get<PaginatedResponse<Notification>>('/notifications', {
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

    const fetchUnreadCount = async (): Promise<void> => {
        try {
            const response = await api.get<{ count: number }>('/notifications/unread-count');
            unreadCount.value = response.data.count;
        } catch (error) {
            console.error('Failed to fetch unread count:', error);
        }
    };

    const markAsRead = async (notificationId: number): Promise<void> => {
        try {
            await api.post(`/notifications/${notificationId}/read`);

            const notification = notifications.value.find(n => n.id === notificationId);
            if (notification) {
                notification.read_at = new Date().toISOString();
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    };

    const markAllAsRead = async (): Promise<void> => {
        try {
            await api.post('/notifications/mark-all-read');

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

    const initialize = async (): Promise<void> => {
        await fetchUnreadCount();
    };

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
