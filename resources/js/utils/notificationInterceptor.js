/**
 * Notification Interceptor for Dashboard Updates
 * 
 * This utility intercepts push notifications and dispatches custom events
 * that the Dashboard can listen to for real-time stats updates.
 */

class NotificationInterceptor {
    constructor() {
        this.setupServiceWorkerListener();
    }

    setupServiceWorkerListener() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', (event) => {
                console.log('📨 Notification interceptor received message:', event.data);

                if (event.data && event.data.type === 'PUSH_NOTIFICATION') {
                    console.log('🎯 Processing PUSH_NOTIFICATION in interceptor');
                    this.handlePushNotification(event.data.payload);
                } else {
                    console.log('❌ Message type not PUSH_NOTIFICATION:', event.data?.type);
                }
            });

            console.log('✅ Notification interceptor set up');
        } else {
            console.warn('❌ Service Worker not supported in notification interceptor');
        }
    }

    handlePushNotification(payload) {
        console.log('🔔 Processing push notification:', payload);

        // Extract notification type from different possible locations in payload
        let notificationType = null;

        // Check various possible locations for the notification type
        // Pusher Beams structure: payload.notification.data.type
        if (payload.notification?.data?.type) {
            notificationType = payload.notification.data.type;
            console.log('📍 Found type in payload.notification.data.type:', notificationType);
        }
        // Direct data structure
        else if (payload.data?.type) {
            notificationType = payload.data.type;
            console.log('📍 Found type in payload.data.type:', notificationType);
        }
        // Root level type
        else if (payload.type) {
            notificationType = payload.type;
            console.log('📍 Found type in payload.type:', notificationType);
        }
        // Infer from notification title
        else if (payload.notification?.title) {
            const title = payload.notification.title.toLowerCase();
            if (title.includes('pago') || title.includes('paid')) {
                notificationType = 'pix_paid';
                console.log('📍 Inferred type from notification title:', notificationType);
            } else if (title.includes('expirado') || title.includes('expired')) {
                notificationType = 'pix_expired';
                console.log('📍 Inferred type from notification title:', notificationType);
            }
        }
        // Infer from root title
        else if (payload.title) {
            const title = payload.title.toLowerCase();
            if (title.includes('pago') || title.includes('paid')) {
                notificationType = 'pix_paid';
                console.log('📍 Inferred type from title:', notificationType);
            } else if (title.includes('expirado') || title.includes('expired')) {
                notificationType = 'pix_expired';
                console.log('📍 Inferred type from title:', notificationType);
            }
        }

        console.log('📝 Final detected notification type:', notificationType);

        if (notificationType === 'pix_paid' || notificationType === 'pix_expired') {
            // Dispatch custom event for Dashboard to listen to
            const customEvent = new CustomEvent('pix-status-update', {
                detail: {
                    type: notificationType,
                    payload: payload
                }
            });

            window.dispatchEvent(customEvent);
            console.log('🎯 Dispatched pix-status-update event:', notificationType);
        } else {
            console.log('❌ Could not determine notification type from payload');
            console.log('🔍 Payload structure for debugging:', JSON.stringify(payload, null, 2));
        }
    }
}

// Initialize the interceptor
const interceptor = new NotificationInterceptor();

export default interceptor;
