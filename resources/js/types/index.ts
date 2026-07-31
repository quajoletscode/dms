export * from './auth';

export type Message = {
    success: string | null;
    error: string | null;
    warning: string | null;
    info: string | null;
};

export type Request = {
    q: string | null;
    per_page: number | null;
    [key: string]: any;
};

export interface Breadcrumb {
    label: string;
    url?: string;
}

export interface SimplePaginationMeta {
    currentPage: number;
    nextPageUrl: string | null;
    prevPageUrl: string | null;
    perPage: number;
    from: number;
    to: number;
}

export interface SelectOption {
    label: string;
    value: string | number | any;
}

export type ColumnDef = string | { label: string; key: string };
