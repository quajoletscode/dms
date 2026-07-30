import { router } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';

export function useInertiaLoader(urlFilter?: string) {
    const isProcessing = ref(false);

    const unbindStart = router.on('start', (event) => {
        // If no filter is provided, or the URL matches the filter
        if (!urlFilter || event.detail.visit.url.pathname === urlFilter) {
            isProcessing.value = true;
        }
    });

    const unbindFinish = router.on('finish', () => {
        isProcessing.value = false;
    });

    onUnmounted(() => {
        unbindStart();
        unbindFinish();
    });

    return { isProcessing };
}
