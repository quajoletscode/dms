import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Request } from '@/types';

export type SortDirection = 'asc' | 'desc';

export function useSort(only?: string[]) {
    const page = usePage();

    const sort = computed<string | null>(
        () =>
            (page.props.request as unknown as Request | undefined)?.sort ??
            null,
    );

    const direction = computed<SortDirection | null>(() => {
        const dir = (page.props.request as unknown as Request | undefined)
            ?.direction;

        return dir === 'asc' || dir === 'desc' ? dir : null;
    });

    const toggleSort = (column: string) => {
        const cur = sort.value;
        const curDir = direction.value;

        let newSort: string | undefined;
        let newDir: SortDirection | undefined;

        if (cur !== column) {
            // New column: start ascending
            newSort = column;
            newDir = 'asc';
        } else if (curDir === 'asc') {
            // Same column ascending -> descending
            newSort = column;
            newDir = 'desc';
        }
        // else descending -> clear sort (newSort and newDir remain undefined)

        const request = (page.props.request as unknown as
            Request | undefined) ?? { q: null, per_page: null };

        router.get(
            '',
            { ...request, sort: newSort, direction: newDir, page: undefined },
            { preserveState: true, replace: true, preserveScroll: false, only },
        );
    };

    return { sort, direction, toggleSort };
}
