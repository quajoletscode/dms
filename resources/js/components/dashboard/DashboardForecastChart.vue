<script lang="ts" setup>
import type { ApexOptions } from 'apexcharts';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import type { ReportChart } from '@/types/reports';

const props = defineProps<{
    chart: ReportChart;
}>();

const palette = ['#3b82f6', '#8b5cf6', '#10b981', '#f97316', '#f43f5e', '#06b6d4'];

const baseName = (name: string) => name.replace(/\s*\(Forecast\)\s*$/, '');

const colorFor = (() => {
    const assigned = new Map<string, string>();

    return (name: string) => {
        const base = baseName(name);

        if (!assigned.has(base)) {
            assigned.set(base, palette[assigned.size % palette.length]);
        }

        return assigned.get(base) as string;
    };
})();

const isDonut = computed(() => props.chart.type === 'donut');

const hasData = computed(() => props.chart.series.some((s) => s.data.some((v) => v !== null && v !== undefined)));

const seriesColors = computed(() => props.chart.series.map((s) => colorFor(s.name)));

const series = computed(() => {
    if (isDonut.value) {
        return props.chart.series[0]?.data.map((v) => v ?? 0) ?? [];
    }

    return props.chart.series.map((s) => ({ name: s.name, data: s.data }));
});

const dashArray = computed(() => props.chart.series.map((s) => (s.isForecast ? 6 : 0)));

const options = computed<ApexOptions>(() => {
    if (isDonut.value) {
        return {
            chart: { type: 'donut', height: 260, background: 'transparent' },
            theme: { mode: 'light' },
            labels: props.chart.categories,
            colors: palette,
            legend: { position: 'bottom', fontSize: '11px', labels: { colors: '#64748b' } },
            dataLabels: { enabled: false },
            plotOptions: { pie: { donut: { size: '65%' } } },
            tooltip: { y: { formatter: (v: number) => v.toLocaleString('en-US', { maximumFractionDigits: 2 }) } },
        };
    }

    return {
        chart: { type: props.chart.type, height: 260, toolbar: { show: false }, background: 'transparent' },
        theme: { mode: 'light' },
        colors: seriesColors.value,
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: props.chart.type === 'bar' ? 0 : 2,
            dashArray: dashArray.value,
        },
        fill:
            props.chart.type === 'area'
                ? { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02 } }
                : { opacity: 1 },
        plotOptions: { bar: { columnWidth: '55%', borderRadius: 3 } },
        markers: { size: 0 },
        xaxis: {
            categories: props.chart.categories,
            labels: { rotate: -45, style: { fontSize: '10px', colors: '#94a3b8' } },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: {
                formatter: (v: number) => v.toLocaleString('en-US', { notation: 'compact', maximumFractionDigits: 1 }),
                style: { fontSize: '10px', colors: '#94a3b8' },
            },
        },
        legend: { position: 'top', fontSize: '11px', labels: { colors: '#64748b' } },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 3 },
        tooltip: {
            y: { formatter: (v: number) => (v === null ? '-' : v.toLocaleString('en-US', { maximumFractionDigits: 2 })) },
        },
    };
});
</script>

<template>
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ chart.title }}</h3>
            <span
                v-if="chart.series.some((s) => s.isForecast)"
                class="inline-flex items-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-2 py-0.5 text-[10px] font-medium tracking-wide text-violet-700 uppercase dark:border-violet-900/40 dark:bg-violet-950/20 dark:text-violet-300"
            >
                <span class="h-1.5 w-3 border-t border-dashed border-violet-500"></span>
                Forecast
            </span>
        </div>
        <VueApexCharts v-if="hasData" :type="chart.type" height="260" :options="options" :series="series" />
        <div v-else class="flex h-52 items-center justify-center text-sm text-slate-400 dark:text-slate-500">No data available for this period</div>
    </div>
</template>
