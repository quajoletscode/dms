import { ref } from 'vue';

export type ToastType = 'success' | 'error' | 'info' | 'warning';

export interface Toast {
    id: number;
    message: string;
    type: ToastType;
    duration?: number;
}

const toasts = ref<Toast[]>([]);

export function useToast() {
    const addToast = (
        message: string,
        type: ToastType = 'info',
        duration = 5000,
    ) => {
        const id = Date.now();
        toasts.value.push({ id, message, type, duration });

        setTimeout(() => {
            removeToast(id);
        }, duration);
    };

    const removeToast = (id: number) => {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    };

    return { toasts, addToast, removeToast };
}
