<script lang="ts" setup>
      import { watch } from 'vue';
      import { usePage } from '@inertiajs/vue3';
      import Button from '@/components/ui/Button.vue';
      import Modal from '@/components/ui/modal/Modal.vue';
      import TransactionSummaryCard from '@/components/ui/TransactionSummaryCard.vue';
      import { useTransactionSummaryModal } from '@/composables/useTransactionSummaryModal';
      import type { TransactionSummary } from '@/types/transaction-summary';

      const { isOpen, summary, open, close } = useTransactionSummaryModal();

      const page = usePage();

      watch(
            () => page.props.message,
            (message) => {
                  const value = (message as { transaction_summary?: TransactionSummary | null } | undefined)?.transaction_summary;

                  if (value) {
                        open(value);
                  }
            },
            { immediate: true, deep: true },
      );
</script>

<template>
      <Modal title="Transaction Summary" :is-open="isOpen" size="md" @close="close">
            <TransactionSummaryCard v-if="summary" :summary="summary" />

            <template #footer>
                  <Button variant="primary" @click="close">Done</Button>
            </template>
      </Modal>
</template>

<style scoped></style>
