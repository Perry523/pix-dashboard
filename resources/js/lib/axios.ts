import axios, { type AxiosInstance } from 'axios';
import { usePage } from '@inertiajs/vue3';

const api: AxiosInstance = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

const getToken = (): string | null => {
    const storedToken = localStorage.getItem('api_token');
    if (storedToken) {
        return storedToken;
    }

    try {
        const page = usePage();
        return page.props.auth?.token || null;
    } catch {
        return null;
    }
};

export const setAuthToken = (token: string): void => {
    localStorage.setItem('api_token', token);
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
};

export const removeAuthToken = (): void => {
    localStorage.removeItem('api_token');
    delete api.defaults.headers.common['Authorization'];
};

api.interceptors.request.use(
    (config) => {
        const token = getToken();
        if (token && config.headers) {
            config.headers.Authorization = `Bearer ${token}`;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken && config.headers) {
            config.headers['X-CSRF-TOKEN'] = csrfToken;
        }

        return config;
    },
    (error) => Promise.reject(error)
);

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            removeAuthToken();
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default api;
