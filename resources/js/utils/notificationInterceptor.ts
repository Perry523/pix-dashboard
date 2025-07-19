interface NotificationPayload {
    notification?: {
        data?: {
            type?: string;
        };
        title?: string;
    };
    data?: {
        type?: string;
    };
    type?: string;
    title?: string;
}

interface ServiceWorkerMessage {
    data: {
        type: string;
        payload: NotificationPayload;
    };
}

type PixNotificationType = 'pix_paid' | 'pix_expired' | 'pix_created';

class NotificationInterceptor {
    constructor() {
        this.setupServiceWorkerListener();
    }

    private setupServiceWorkerListener(): void {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', (event: MessageEvent<ServiceWorkerMessage['data']>) => {
                if (event.data && event.data.type === 'PUSH_NOTIFICATION') {
                    this.handlePushNotification(event.data.payload);
                }
            });
        }
    }

    private handlePushNotification(payload: NotificationPayload): void {
        let notificationType: PixNotificationType | null = null;

        if (payload.notification?.data?.type) {
            notificationType = payload.notification.data.type as PixNotificationType;
        } else if (payload.data?.type) {
            notificationType = payload.data.type as PixNotificationType;
        } else if (payload.type) {
            notificationType = payload.type as PixNotificationType;
        } else if (payload.notification?.title) {
            const title = payload.notification.title.toLowerCase();
            if (title.includes('pago') || title.includes('paid')) {
                notificationType = 'pix_paid';
            } else if (title.includes('expirado') || title.includes('expired')) {
                notificationType = 'pix_expired';
            } else if (title.includes('criado') || title.includes('created')) {
                notificationType = 'pix_created';
            }
        } else if (payload.title) {
            const title = payload.title.toLowerCase();
            if (title.includes('pago') || title.includes('paid')) {
                notificationType = 'pix_paid';
            } else if (title.includes('expirado') || title.includes('expired')) {
                notificationType = 'pix_expired';
            } else if (title.includes('criado') || title.includes('created')) {
                notificationType = 'pix_created';
            }
        }

        if (notificationType === 'pix_paid' || notificationType === 'pix_expired' || notificationType === 'pix_created') {
            const customEvent = new CustomEvent('pix-status-update', {
                detail: {
                    type: notificationType,
                    payload: payload
                }
            });

            window.dispatchEvent(customEvent);
        }
    }
}

const interceptor = new NotificationInterceptor();

export default interceptor;
