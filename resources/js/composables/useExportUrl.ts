import { computed, toValue } from 'vue';
import type { MaybeRefOrGetter } from 'vue';

type FilterValue = string | number | boolean | null | undefined;
type Filters = Record<string, FilterValue>;

export function useExportUrl(
    baseUrl: MaybeRefOrGetter<string>,
    filters: MaybeRefOrGetter<Filters>,
) {
    const buildUrl = (format: 'csv' | 'pdf') => {
        const base = toValue(baseUrl).replace(/\/$/, '');
        const filterValues = toValue(filters);

        const params = new URLSearchParams();

        for (const [key, value] of Object.entries(filterValues)) {
            if (value !== null && value !== undefined && value !== '') {
                params.set(key, String(value));
            }
        }

        const query = params.toString();

        return query ? `${base}/${format}?${query}` : `${base}/${format}`;
    };

    return {
        csvUrl: computed(() => buildUrl('csv')),
        pdfUrl: computed(() => buildUrl('pdf')),
    };
}
