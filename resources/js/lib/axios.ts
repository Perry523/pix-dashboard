import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

// Create axios instance
const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Function to get token from localStorage or Inertia page props
const getToken = (): string | null => {
    // First try localStorage
    const storedToken = localStorage.getItem('api_token');
    if (storedToken) {
        return storedToken;
    }
    
    // Fallback to Inertia page props
    try {
        const page = usePage();
        return page.props.auth?.token || null;
    } catch {
        return null;
    }
};

// Function to store token in localStorage
export const setAuthToken = (token: string) => {
    localStorage.setItem('api_token', token);
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
};

// Function to remove token
export const removeAuthToken = () => {
    localStorage.removeItem('api_token');
    delete api.defaults.headers.common['Authorization'];
};

// Request interceptor to add token
api.interceptors.request.use(
    (config) => {
        const token = getToken();
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        
        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            config.headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor to handle auth errors
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Token expired or invalid, remove it
            removeAuthToken();
            // Optionally redirect to login
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default api;
