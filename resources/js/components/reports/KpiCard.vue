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
        return 'text-slate-400';
    }

    return props.kpi.trend > 0 ? 'text-emerald-400' : 'text-rose-400';
});
</script>

<template>
    <div
        class="relative overflow-hidden rounded-xl border border-slate-700/60 bg-slate-900/60 p-5 backdrop-blur-xl transition hover:border-cyan-500/40"
    >
        <div
            class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/60 to-transparent"
        ></div>
        <div class="flex items-center justify-between">
            <p
                class="text-xs font-medium tracking-wide text-slate-400 uppercase"
            >
                {{ kpi.label }}
            </p>
            <div class="rounded-lg bg-slate-800/80 p-2 text-cyan-400">
                <component :is="icon" class="h-4 w-4" />
            </div>
        </div>
        <p class="mt-3 font-mono text-2xl font-bold text-slate-100">
            {{ formatReportValue(kpi.value, kpi.format) }}
        </p>
        <p
            v-if="kpi.trend !== null && kpi.trend !== undefined"
            :class="[
                'mt-1 flex items-center gap-1 text-xs font-medium',
                trendClass,
            ]"
        >
            <component :is="trendIcon" class="h-3 w-3" />
            {{ formatTrend(kpi.trend) }} vs previous period
        </p>
    </div>
</template>
