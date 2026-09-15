<script setup lang="ts" generic="T">
import { router, usePage } from '@inertiajs/vue3';
import { RefreshCcwIcon, SearchIcon } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { debounce } from '@/composables/useApp';
import { useSort } from '@/composables/useSort';
import type { ColumnDef, Request, SimplePaginationMeta } from '@/types';
import Button from '../ui/Button.vue';
import TextInput from '../ui/inputs/TextInput.vue';
import BaseTable from './BaseTable.vue';
import SimplePagination from './SimplePagination.vue';

const props = defineProps<{
    items: T[] | undefined;
    thead: ColumnDef[];
    isLoading: boolean;
    meta?: SimplePaginationMeta;
    searchPlaceholder?: string;
    only?: string[];
    stickyLastColumn?: boolean;
}>();

const page = usePage();

const { sort, direction, toggleSort } = useSort(props.only);

const getNestedVal = (obj: unknown, path: string): unknown =>
    path
        .split('.')
        .reduce<unknown>(
            (acc, k) =>
                acc != null ? (acc as Record<string, unknown>)[k] : undefined,
            obj,
        );

const sortedItems = computed<T[]>(() => {
    if (!sort.value || !props.items?.length) {
        return props.items ?? [];
    }

    const key = sort.value;
    const dir = direction.value ?? 'asc';

    return [...props.items].sort((a, b) => {
        const av = getNestedVal(a, key);
        const bv = getNestedVal(b, key);

        if (av == null && bv == null) {
            return 0;
        }

        if (av == null) {
            return dir === 'asc' ? 1 : -1;
        }

        if (bv == null) {
            return dir === 'asc' ? -1 : 1;
        }

        const cmp = String(av).localeCompare(String(bv), undefined, {
            numeric: true,
            sensitivity: 'base',
        });

        return dir === 'desc' ? -cmp : cmp;
    });
});

const getRequest = () =>
    (page.props.request as unknown as Request | undefined) ?? {
        q: null,
        per_page: null,
    };

const searchTerm = ref(getRequest().q ?? '');

const getCurrentParams = () => {
    const request = getRequest();

    return {
        ...request,
        q: request.q ?? searchTerm.value,
        per_page: Number(request.per_page ?? props.meta?.perPage ?? 15),
    };
};

const reloadTable = (payload: { q: string; per_page: number }) => {
    const current = getCurrentParams();

    router.get(
        '',
        {
            ...current,
            q: payload.q,
            per_page: payload.per_page,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: props.only,
        },
    );
};

const debouncedEmitSearch = debounce((term: string) => {
    const current = getCurrentParams();

    reloadTable({
        q: term,
        per_page: current.per_page,
    });
}, 800);

watch(searchTerm, (newVal) => {
    debouncedEmitSearch(newVal);
});
</script>

<template>
    <div class="flex w-full flex-col">
        <div
            class="mb-4 flex basis-full flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
        >
            <slot name="actions">
                <span></span>
            </slot>
            <div class="flex items-center gap-3">
                <Button
                    variant="ghost"
                    class="cursor-pointer"
                    title="Click to refresh the data"
                >
                    <RefreshCcwIcon
                        :size="18"
                        class="text-gray-400"
                        @click="reloadTable(getCurrentParams())"
                    />
                </Button>
                <div class="group relative w-full lg:w-96 print:hidden">
                    <TextInput
                        type="search"
                        class="pr-2 pl-10"
                        v-model="searchTerm"
                        size="md"
                        :placeholder="searchPlaceholder"
                    >
                        <template #leading>
                            <div
                                class="absolute top-1/2 left-2.5 z-1 -translate-y-1/2 cursor-pointer"
                            >
                                <SearchIcon
                                    :size="18"
                                    class="group-focus-within:text-global-light text-gray-400"
                                />
                            </div>
                        </template>
                    </TextInput>
                </div>
            </div>
        </div>

        <div class="relative min-h-50">
            <div
                v-if="isLoading"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 backdrop-blur-[1px] dark:bg-slate-900/50"
            >
                <div
                    class="border-primary-light h-8 w-8 animate-spin rounded-full border-b-2"
                ></div>
            </div>

            <BaseTable
                :thead="props.thead"
                :sort="sort"
                :direction="direction"
                :sticky-last-column="props.stickyLastColumn"
                @sort="toggleSort"
            >
                <slot :items="sortedItems" />

                <template #empty v-if="items?.length === 0 && !isLoading">
                    <div class="py-12 text-center">
                        <slot name="empty-state">
                            <span class="font-medium text-gray-400"
                                >No records available.</span
                            >
                        </slot>
                    </div>
                </template>
            </BaseTable>
        </div>

        <SimplePagination
            :meta="props.meta"
            :only="props.only"
            :route-url="''"
        />
    </div>
</template>
