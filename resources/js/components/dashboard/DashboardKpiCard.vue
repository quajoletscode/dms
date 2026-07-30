<script lang="ts" setup>
import { ArrowDownRight, ArrowUpRight, Minus } from '@lucide/vue';
import { computed } from 'vue';
import { formatReportValue, formatTrend } from '@/composables/useReportFormat';
import { getReportIcon } from '@/composables/useReportIcons';
import type { ReportKpi } from '@/types/reports';

const props = defineProps<{
    kpi: ReportKpi;
}>();

const icon = computed(() => getReportIcon(props.kpi.icon));

const trendIcon = computed(() => {
    if (!props.kpi.trend) {
        return Minus;
    }

    return props.kpi.trend > 0 ? ArrowUpRight : ArrowDownRight;
});

const trendClass = computed(() => {
    if (!props.kpi.trend) {
        return 'text-slate-400 dark:text-slate-500';
    }

    return props.kpi.trend > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400';
});
</script>

<template>
    <div
        class="rounded-lg p-5 shadow-sm dark:bg-linear-to-l dark:from-slate-700 dark:to-slate-800 bg-linear-to-l from-emerald-200/10 to-white"
    >
        <div class="flex items-center justify-between">
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ kpi.label }}</p>
            <div class="rounded-lg bg-slate-100 p-2 dark:bg-slate-600/60">
                <component :is="icon" class="h-4 w-4 text-slate-500 dark:text-slate-300" />
            </div>
        </div>
        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">
            {{ formatReportValue(kpi.value, kpi.format) }}
        </p>
        <p v-if="kpi.trend !== null && kpi.trend !== undefined" :class="['mt-1.5 flex items-center gap-1 text-xs font-medium', trendClass]">
            <component :is="trendIcon" class="h-3 w-3" />
            {{ formatTrend(kpi.trend) }} vs previous period
        </p>
    </div>
</template>
