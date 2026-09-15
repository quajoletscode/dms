<script lang="ts" setup>
import { formatReportValue } from '@/composables/useReportFormat';
import type { ReportTable } from '@/types/reports';

defineProps<{
    table: ReportTable;
}>();

const cellValue = (
    row: Record<string, any>,
    column: ReportTable['columns'][number],
) => {
    const value = row[column.key];

    if (
        column.format &&
        (column.format === 'currency' ||
            column.format === 'number' ||
            column.format === 'percent' ||
            column.format === 'days')
    ) {
        return formatReportValue(value, column.format);
    }

    return value ?? '—';
};
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-slate-700/60 bg-slate-900/60 backdrop-blur-xl"
    >
        <div class="border-b border-slate-700/60 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-200">
                {{ table.title }}
            </h3>
        </div>
        <div
            v-if="table.rows.length === 0"
            class="flex h-32 items-center justify-center text-sm text-slate-500"
        >
            No data available
        </div>
        <div v-else class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr
                        class="border-b border-slate-700/60 text-xs tracking-wide text-slate-400 uppercase"
                    >
                        <th
                            v-for="column in table.columns"
                            :key="column.key"
                            class="px-5 py-3 font-medium"
                        >
                            {{ column.label }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, i) in table.rows"
                        :key="i"
                        class="border-b border-slate-800/60 odd:bg-slate-800/20"
                    >
                        <td
                            v-for="column in table.columns"
                            :key="column.key"
                            class="px-5 py-3 text-slate-300"
                            :class="{
                                'font-mono':
                                    column.format === 'currency' ||
                                    column.format === 'number' ||
                                    column.format === 'percent' ||
                                    column.format === 'days',
                            }"
                        >
                            {{ cellValue(row, column) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
