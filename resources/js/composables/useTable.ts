import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { SimplePaginationMeta } from '@/types';

export function useTable<T>(key: string = 'data') {
    const page = usePage();

    const dataSource = computed(
        () =>
            (page.props[key] ?? {}) as {
                data?: T[] | null;
                current_page?: number | null;
                next_page_url?: string | null;
                prev_page_url?: string | null;
                per_page?: number | null;
                from?: number | null;
                to?: number | null;
            },
    );

    const data = computed<T[]>(() => {
        return Array.isArray(dataSource.value.data)
            ? (dataSource.value.data as T[])
            : [];
    });

    const table_meta = computed<SimplePaginationMeta>(() => {
        const p = dataSource.value;

        return {
            currentPage: Number(p.current_page ?? 1),
            nextPageUrl: (p.next_page_url ?? null) as string | null,
            prevPageUrl: (p.prev_page_url ?? null) as string | null,
            perPage: Number(p.per_page ?? 15),
            from: Number(p.from ?? 0),
            to: Number(p.to ?? 0),
        };
    });

    return { data, table_meta };
}
