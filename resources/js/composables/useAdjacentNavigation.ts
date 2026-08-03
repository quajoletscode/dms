import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

/**
 * Wires ArrowLeft/ArrowRight to prev/next detail-page navigation. Ignores
 * keystrokes while the user is typing into a form field.
 */
export function useAdjacentNavigation(getPrevUrl: () => string | null, getNextUrl: () => string | null) {
    function isTypingTarget(target: EventTarget | null): boolean {
        if (!(target instanceof HTMLElement)) {
            return false;
        }

        return target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT' || target.isContentEditable;
    }

    function handleKeydown(event: KeyboardEvent) {
        if (isTypingTarget(event.target)) {
            return;
        }

        if (event.key === 'ArrowLeft') {
            const url = getPrevUrl();
            if (url) {
                router.visit(url);
            }
        } else if (event.key === 'ArrowRight') {
            const url = getNextUrl();
            if (url) {
                router.visit(url);
            }
        }
    }

    onMounted(() => window.addEventListener('keydown', handleKeydown));
    onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
}
