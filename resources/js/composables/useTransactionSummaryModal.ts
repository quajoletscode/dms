import { ref } from 'vue';
import type { TransactionSummary } from '@/types/transaction-summary';

const isOpen = ref(false);
const summary = ref<TransactionSummary | null>(null);

export function useTransactionSummaryModal() {
    const open = (value: TransactionSummary) => {
        summary.value = value;
        isOpen.value = true;
    };

    const close = () => {
        isOpen.value = false;
    };

    return { isOpen, summary, open, close };
}
