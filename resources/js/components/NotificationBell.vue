<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useNotifications } from '@/composables/useNotifications';
import { Bell, Check, CheckCheck } from 'lucide-vue-next';
import { onMounted } from 'vue';

const {
    notifications,
    unreadCount,
    isLoading,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
} = useNotifications();

const handleNotificationClick = async (notification: any) => {
    if (!notification.read_at) {
        await markAsRead(notification.id);
    }

    if (notification.data?.url) {
        window.location.href = notification.data.url;
    }
};

const handleMarkAllRead = async () => {
    await markAllAsRead();
};

const formatTimeAgo = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);
    
    if (diffInSeconds < 60) return 'agora';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h`;
    return `${Math.floor(diffInSeconds / 86400)}d`;
};

const getNotificationIcon = (type: string) => {
    switch (type) {
        case 'pix_paid':
            return '💰';
        case 'pix_expired':
            return '⏰';
        default:
            return '📢';
    }
};

onMounted(() => {
    fetchNotifications();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="h-5 w-5" />
                <Badge 
                    v-if="unreadCount > 0" 
                    variant="destructive" 
                    class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center text-xs p-0 min-w-[20px]"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        
        <DropdownMenuContent align="end" class="w-80">
            <div class="flex items-center justify-between p-2">
                <h3 class="font-semibold">Notificações</h3>
                <Button 
                    v-if="unreadCount > 0"
                    variant="ghost" 
                    size="sm" 
                    @click="handleMarkAllRead"
                    class="text-xs"
                >
                    <CheckCheck class="h-3 w-3 mr-1" />
                    Marcar todas
                </Button>
            </div>
            
            <DropdownMenuSeparator />
            
            <ScrollArea class="h-96">
                <div v-if="isLoading" class="p-4 text-center text-muted-foreground">
                    Carregando...
                </div>
                
                <div v-else-if="notifications.length === 0" class="p-4 text-center text-muted-foreground">
                    Nenhuma notificação
                </div>
                
                <div v-else>
                    <DropdownMenuItem
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="flex items-start gap-3 p-3 cursor-pointer"
                        :class="{ 'bg-blue-50': !notification.read_at }"
                        @click="handleNotificationClick(notification)"
                    >
                        <div class="text-lg">{{ getNotificationIcon(notification.type) }}</div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium text-sm truncate">{{ notification.title }}</h4>
                                <span class="text-xs text-muted-foreground ml-2">
                                    {{ formatTimeAgo(notification.created_at) }}
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground mt-1 line-clamp-2">
                                {{ notification.message }}
                            </p>
                        </div>
                        
                        <div v-if="!notification.read_at" class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                    </DropdownMenuItem>
                </div>
            </ScrollArea>
            
            <DropdownMenuSeparator />
            
            <DropdownMenuItem class="justify-center p-2">
                <Button variant="ghost" size="sm" class="w-full" @click="$router.push('/notifications')">
                    Ver todas as notificações
                </Button>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
