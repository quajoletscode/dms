<script lang="ts" setup>
      import { computed } from 'vue';
      import { formatCurrency } from '@/composables/useApp';
      import type { TransactionSummary } from '@/types/transaction-summary';

      const props = defineProps<{
            summary: TransactionSummary;
      }>();

      const isTransfer = computed(() => !!props.summary.secondary_party_name);

      const metaEntries = computed(() =>
            Object.entries(props.summary.meta ?? {}).filter(([, value]) => value !== null && value !== ''),
      );

      const formatMetaLabel = (key: string) =>
            key
                  .replace(/_/g, ' ')
                  .replace(/\b\w/g, (c) => c.toUpperCase());

      const formatValue = (value: string | number, unit?: string | null) => {
            if (typeof value === 'number') {
                  return unit ? `${value.toLocaleString()} ${unit}` : value.toLocaleString();
            }

            return unit ? `${value} ${unit}` : value;
      };
</script>

<template>
      <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                  <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ summary.transaction_label }}</h3>
                  <span
                    class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        {{ summary.party_type }}: {{ summary.party_name }}
                  </span>
            </div>

            <div v-if="isTransfer" class="grid gap-4 sm:grid-cols-2">
                  <div class="min-w-0 rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                              {{ summary.party_type }} ({{ summary.party_name }})
                        </p>
                        <p class="mt-2 text-2xl font-bold break-words text-red-600">
                              {{ summary.source_balance != null ? formatCurrency(summary.source_balance, summary.currency) : '—' }}
                        </p>
                  </div>
                  <div class="min-w-0 rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                              {{ summary.secondary_party_type }} ({{ summary.secondary_party_name }})
                        </p>
                        <p class="mt-2 text-2xl font-bold break-words text-emerald-600">
                              {{ summary.destination_balance != null ? formatCurrency(summary.destination_balance, summary.currency) : '—' }}
                        </p>
                  </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                  <div v-if="summary.transaction_amount != null"
                    class="min-w-0 rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                              {{ summary.amount_paid != null ? 'Amount Paid' : 'Amount' }}
                        </p>
                        <p class="mt-2 break-words text-2xl font-bold text-slate-900 dark:text-white">
                              {{ formatCurrency(summary.amount_paid ?? summary.transaction_amount, summary.currency) }}
                        </p>
                  </div>

                  <div v-if="!isTransfer && summary.previous_balance != null"
                    class="min-w-0 rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Previous Balance</p>
                        <p class="mt-2 break-words text-2xl font-bold text-slate-900 dark:text-white">
                              {{ formatCurrency(summary.previous_balance, summary.currency) }}
                        </p>
                  </div>

                  <div v-if="!isTransfer && summary.current_balance != null"
                    class="min-w-0 rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Remaining Balance</p>
                        <p class="mt-2 break-words text-2xl font-bold" :class="summary.current_balance >= 0 ? 'text-emerald-600' : 'text-red-600'">
                              {{ formatCurrency(summary.current_balance, summary.currency) }}
                        </p>
                  </div>
            </div>

            <dl v-if="summary.line_items?.length"
              class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white p-4 dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-900">
                  <div v-for="line in summary.line_items" :key="line.label" class="flex items-center justify-between gap-3 py-2 first:pt-0 last:pb-0">
                        <dt class="min-w-0 shrink-0 text-sm text-slate-600 dark:text-slate-400">{{ line.label }}</dt>
                        <dd class="min-w-0 break-words text-right text-sm font-semibold" :class="line.emphasis ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300'">
                              {{ formatValue(line.value, line.unit) }}
                        </dd>
                  </div>
            </dl>

            <div v-if="metaEntries.length" class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                  <span v-for="[key, value] in metaEntries" :key="key" class="min-w-0 break-words">
                        {{ formatMetaLabel(key) }}: <span class="font-medium text-slate-700 dark:text-slate-300">{{ value }}</span>
                  </span>
            </div>
      </div>
</template>

<style scoped></style>
