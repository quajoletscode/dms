export type ReportValueFormat = 'currency' | 'number' | 'percent' | 'days';

export type ReportChartType = 'area' | 'bar' | 'donut' | 'line';

export interface ReportChartSeries {
    name: string;
    data: Array<number | null | undefined>;
    isForecast?: boolean;
}

export interface ReportChart {
    title: string;
    type: ReportChartType;
    categories: string[];
    series: ReportChartSeries[];
}

export interface ReportKpi {
    label: string;
    value: number | null | undefined;
    format: ReportValueFormat;
    trend?: number | null;
    icon?: string | null;
}

export type ReportInsightType = 'danger' | 'info' | 'success' | 'warning';

export interface ReportInsight {
    type: ReportInsightType;
    message: string;
}

export interface ReportTableColumn {
    key: string;
    label: string;
    format?: ReportValueFormat;
}

export interface ReportTable {
    title: string;
    columns: ReportTableColumn[];
    rows: Array<Record<string, unknown>>;
}
