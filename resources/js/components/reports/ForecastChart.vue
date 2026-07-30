<script lang="ts" setup>
    import type { ApexOptions } from 'apexcharts';
    import { computed } from 'vue';
    import VueApexCharts from 'vue3-apexcharts';
    import type { ReportChart } from '@/types/reports';

    const props = defineProps<{
        chart: ReportChart;
    }>();

    const palette = ['#22d3ee', '#a78bfa', '#34d399', '#fb923c', '#f472b6', '#60a5fa'];

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
                chart: { type: 'donut', height: 280, background: 'transparent' },
                theme: { mode: 'dark' },
                labels: props.chart.categories,
                colors: palette,
                legend: { position: 'bottom', fontSize: '11px', labels: { colors: '#94a3b8' } },
                dataLabels: { enabled: false },
                stroke: { colors: ['#0f172a'] },
                plotOptions: { pie: { donut: { size: '65%', labels: { show: false } } } },
                tooltip: { theme: 'dark', y: { formatter: (v: number) => v.toLocaleString('en-US', { maximumFractionDigits: 2 }) } },
            };
        }

        return {
            chart: { type: props.chart.type, height: 280, toolbar: { show: false }, background: 'transparent' },
            theme: { mode: 'dark' },
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
            legend: { position: 'top', fontSize: '11px', labels: { colors: '#94a3b8' } },
            grid: { borderColor: '#1e293b', strokeDashArray: 3 },
            tooltip: {
                theme: 'dark',
                y: { formatter: (v: number) => (v === null ? '—' : v.toLocaleString('en-US', { maximumFractionDigits: 2 })) },
            },
        };
    });
</script>

<template>
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-200">{{ chart.title }}</h3>
            <span v-if="chart.series.some((s) => s.isForecast)"
              class="inline-flex items-center gap-1.5 rounded-full border border-violet-500/40 bg-violet-500/10 px-2 py-0.5 text-[10px] font-medium tracking-wide text-violet-300 uppercase">
                <span class="h-1.5 w-3 border-t border-dashed border-violet-400"></span>
                Forecast
            </span>
        </div>
        <VueApexCharts v-if="hasData" :type="chart.type" height="280" :options="options" :series="series" />
        <div v-else class="flex h-60 items-center justify-center text-sm text-slate-500">No data available for this period</div>
    </div>
</template>
