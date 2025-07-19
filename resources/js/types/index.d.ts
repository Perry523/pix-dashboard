import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
    token?: string;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface PixRecord {
    id: number;
    user_id: number;
    token: string;
    status: 'generated' | 'paid' | 'expired';
    expires_at: string;
    created_at: string;
    updated_at: string;
    qr_code?: string;
    link?: string;
}

export interface PixStats {
    total: number;
    paid: number;
    expired: number;
    generated: number;
}

export interface PixData {
    id: number;
    token: string;
    status: string;
    expires_at: string;
    qr_code: string;
    link: string;
    payment_link: string;
    qr_code_svg: string;
    qr_code_base64: string;
    created_at: string;
    updated_at: string;
}

export interface NotificationData {
    id: number;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface ApiResponse<T = unknown> {
    data: T;
    message?: string;
    status?: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

export interface FormErrors {
    [key: string]: string | undefined;
}

export interface InertiaForm<T> {
    data: T;
    errors: FormErrors;
    hasErrors: boolean;
    processing: boolean;
    progress: number | null;
    wasSuccessful: boolean;
    recentlySuccessful: boolean;
    isDirty: boolean;
    transform: (callback: (data: T) => T) => InertiaForm<T>;
    defaults: (field?: keyof T, value?: T[keyof T]) => InertiaForm<T>;
    reset: (...fields: (keyof T)[]) => InertiaForm<T>;
    clearErrors: (...fields: (keyof T)[]) => InertiaForm<T>;
    setError: (field: keyof T, value: string) => InertiaForm<T>;
    submit: (method: string, url: string, options?: any) => void;
    get: (url: string, options?: any) => void;
    post: (url: string, options?: any) => void;
    put: (url: string, options?: any) => void;
    patch: (url: string, options?: any) => void;
    delete: (url: string, options?: any) => void;
    cancel: () => void;
}

export type BreadcrumbItemType = BreadcrumbItem;
