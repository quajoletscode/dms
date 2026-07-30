import { ref } from 'vue';

const isOpen = ref(false);
const title = ref('');
const message = ref('');
let resolveCallback: ((value: boolean) => void) | null = null;

export interface ConfirmOptions {
    title?: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
}

const confirmText = ref('Confirm');
const cancelText = ref('Cancel');

export function useConfirm() {
    const Confirm = (options: ConfirmOptions): Promise<boolean> => {
        title.value = options.title || 'Please Confirm';
        message.value = options.message;
        confirmText.value = options.confirmText || 'Confirm';
        cancelText.value = options.cancelText || 'Cancel';

        isOpen.value = true;

        return new Promise((resolve) => {
            resolveCallback = resolve;
        });
    };

    const respond = (result: boolean) => {
        isOpen.value = false;

        if (resolveCallback) {
            resolveCallback(result);
            resolveCallback = null;
        }
    };

    return {
        isOpen,
        title,
        message,
        confirmText,
        cancelText,
        Confirm,
        respond,
    };
}
