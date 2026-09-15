<script lang="ts" setup>
import { computed } from 'vue';
import type { ReportChart } from '@/types/reports';

const props = defineProps<{
    chart: ReportChart;
}>();

const width = 640;
const height = 280;
const padding = 30;
const palette = [
    '#22d3ee',
    '#a78bfa',
    '#34d399',
    '#fb923c',
    '#f472b6',
    '#60a5fa',
];

const hasData = computed(() =>
    props.chart.series.some((series) =>
        series.data.some((value) => value !== null && value !== undefined),
    ),
);

const numericValues = computed(() =>
    props.chart.series
        .flatMap((series) => series.data)
        .filter((value): value is number => typeof value === 'number'),
);

const valueRange = computed(() => {
    const values = numericValues.value;
    const min = Math.min(0, ...values);
    const max = Math.max(1, ...values);

    return { min, max: min === max ? max + 1 : max };
});

const xFor = (index: number, count: number) => {
    if (count <= 1) {
        return width / 2;
    }

    return padding + (index * (width - padding * 2)) / (count - 1);
};

const yFor = (value: number) => {
    const range = valueRange.value.max - valueRange.value.min;

    return (
        height -
        padding -
        ((value - valueRange.value.min) / range) * (height - padding * 2)
    );
};

const linePath = (data: Array<number | null | undefined>) => {
    return data
        .map((value, index) =>
            typeof value === 'number'
                ? `${xFor(index, data.length)},${yFor(value)}`
                : null,
        )
        .filter((point): point is string => point !== null)
        .join(' ');
};

const areaPath = (data: Array<number | null | undefined>) => {
    const points = linePath(data);

    if (!points) {
        return '';
    }

    const firstX = xFor(0, data.length);
    const lastX = xFor(data.length - 1, data.length);
    const baseline = height - padding;

    return `M ${firstX},${baseline} L ${points} L ${lastX},${baseline} Z`;
};

const bars = computed(() => {
    const visibleSeries = props.chart.series.slice(0, palette.length);
    const slotWidth =
        (width - padding * 2) / Math.max(props.chart.categories.length, 1);
    const barWidth = Math.max(
        4,
        (slotWidth * 0.72) / Math.max(visibleSeries.length, 1),
    );

    return visibleSeries.flatMap((series, seriesIndex) =>
        series.data.map((value, index) => {
            const numberValue = typeof value === 'number' ? value : 0;
            const x =
                padding +
                index * slotWidth +
                (slotWidth - barWidth * visibleSeries.length) / 2 +
                seriesIndex * barWidth;
            const y = yFor(Math.max(0, numberValue));

            return {
                color: palette[seriesIndex % palette.length],
                height: height - padding - y,
                key: `${series.name}-${index}`,
                x,
                y,
                width: barWidth - 2,
            };
        }),
    );
});

const donutSegments = computed(() => {
    const values =
        props.chart.series[0]?.data.map((value) => Math.max(0, value ?? 0)) ??
        [];
    const total = values.reduce((sum, value) => sum + value, 0);

    if (total <= 0) {
        return [];
    }

    let offset = 25;

    return values.map((value, index) => {
        const percent = (value / total) * 100;
        const segment = {
            color: palette[index % palette.length],
            dasharray: `${percent} ${100 - percent}`,
            dashoffset: `${offset}`,
            key: props.chart.categories[index] ?? index.toString(),
        };

        offset -= percent;

        return segment;
    });
});
</script>

<template>
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-200">
                {{ chart.title }}
            </h3>
            <span
                v-if="chart.series.some((series) => series.isForecast)"
                class="inline-flex items-center gap-1.5 rounded-full border border-violet-500/40 bg-violet-500/10 px-2 py-0.5 text-[10px] font-medium tracking-wide text-violet-300 uppercase"
            >
                <span
                    class="h-1.5 w-3 border-t border-dashed border-violet-400"
                ></span>
                Forecast
            </span>
        </div>

        <div v-if="hasData" class="h-[280px] w-full">
            <svg
                v-if="chart.type === 'donut'"
                viewBox="0 0 220 220"
                class="h-full w-full"
            >
                <circle
                    cx="110"
                    cy="110"
                    r="74"
                    fill="none"
                    stroke="#1e293b"
                    stroke-width="28"
                />
                <circle
                    v-for="segment in donutSegments"
                    :key="segment.key"
                    cx="110"
                    cy="110"
                    r="74"
                    fill="none"
                    :stroke="segment.color"
                    stroke-width="28"
                    :stroke-dasharray="segment.dasharray"
                    :stroke-dashoffset="segment.dashoffset"
                    pathLength="100"
                    transform="rotate(-90 110 110)"
                />
            </svg>

            <svg
                v-else
                :viewBox="`0 0 ${width} ${height}`"
                class="h-full w-full overflow-visible"
            >
                <line
                    :x1="padding"
                    :x2="width - padding"
                    :y1="height - padding"
                    :y2="height - padding"
                    stroke="#1e293b"
                />
                <g v-if="chart.type === 'bar'">
                    <rect
                        v-for="bar in bars"
                        :key="bar.key"
                        :x="bar.x"
                        :y="bar.y"
                        :width="bar.width"
                        :height="bar.height"
                        rx="3"
                        :fill="bar.color"
                        opacity="0.9"
                    />
                </g>
                <g v-else>
                    <g
                        v-for="(series, index) in chart.series"
                        :key="series.name"
                    >
                        <path
                            v-if="chart.type === 'area'"
                            :d="areaPath(series.data)"
                            :fill="palette[index % palette.length]"
                            opacity="0.14"
                        />
                        <polyline
                            :points="linePath(series.data)"
                            fill="none"
                            :stroke="palette[index % palette.length]"
                            stroke-width="3"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            :stroke-dasharray="
                                series.isForecast ? '8 8' : undefined
                            "
                        />
                    </g>
                </g>
            </svg>
        </div>
        <div
            v-else
            class="flex h-60 items-center justify-center text-sm text-slate-500"
        >
            No data available for this period
        </div>
    </div>
</template>
