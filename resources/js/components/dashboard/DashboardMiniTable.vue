<script lang="ts" setup>
import { formatReportValue } from '@/composables/useReportFormat';
import type { ReportTable } from '@/types/reports';

defineProps<{
    table: ReportTable;
}>();

const cellValue = (row: Record<string, any>, column: ReportTable['columns'][number]) => {
    const value = row[column.key];

    if (column.format && (column.format === 'currency' || column.format === 'number' || column.format === 'percent' || column.format === 'days')) {
        return formatReportValue(value, column.format);
    }

    return value ?? '-';
};
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
        <div v-if="table.rows.length === 0" class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">No data available</div>
        <div v-else class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr class="text-xs tracking-wide text-slate-500 uppercase dark:text-slate-400">
                        <th v-for="column in table.columns" :key="column.key" class="px-4 py-2.5 font-medium">{{ column.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, i) in table.rows" :key="i" class="border-t border-slate-100 dark:border-slate-800">
                        <td
                            v-for="column in table.columns"
                            :key="column.key"
                            class="px-4 py-2.5 text-slate-700 dark:text-slate-300"
                            :class="{ 'font-mono': column.format === 'currency' || column.format === 'number' || column.format === 'percent' || column.format === 'days' }"
                        >
                            {{ cellValue(row, column) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
