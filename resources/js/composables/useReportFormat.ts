import { formatAmount, formatNumber } from '@/composables/useApp';
import type { ReportValueFormat } from '@/types/reports';

export function formatReportValue(
    value: number | null | undefined,
    format: ReportValueFormat | string,
): string {
    if (value === null || value === undefined) {
        return '—';
    }

    switch (format) {
        case 'currency':
            return formatAmount(value);
        case 'percent':
            return `${value.toFixed(1)}%`;
        case 'days':
            return `${formatNumber(value)} ${Math.abs(value) === 1 ? 'day' : 'days'}`;
        case 'number':
        default:
            return formatNumber(value);
    }
}

export function formatTrend(trend: number | null | undefined): string | null {
    if (trend === null || trend === undefined) {
        return null;
    }

    const sign = trend > 0 ? '+' : '';

    return `${sign}${trend.toFixed(1)}%`;
}
