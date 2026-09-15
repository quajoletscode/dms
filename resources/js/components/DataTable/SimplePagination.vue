<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronsLeftIcon, ChevronsRightIcon } from '@lucide/vue';
import type { Request, SimplePaginationMeta } from '@/types';

const props = withDefaults(
    defineProps<{
        meta?: SimplePaginationMeta;
        routeUrl: string;
        perPageOptions?: number[];
        only?: string[];
        preserveScroll?: boolean;
        preserveState?: boolean;
    }>(),
    {
        meta: undefined,
        perPageOptions: () => [10, 15, 25, 50, 100],
        only: undefined,
        preserveScroll: true,
        preserveState: true,
    },
);

const page = usePage();

const getRequest = () =>
    (page.props.request as unknown as Request | undefined) ?? {
        q: null,
        per_page: null,
    };

const handlePerPageChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const current = getRequest();

    router.get(
        props.routeUrl,
        {
            ...current,
            per_page: Number(target.value),
        },
        {
            preserveState: props.preserveState,
            preserveScroll: props.preserveScroll,
            replace: true,
            only: props.only,
        },
    );
};
</script>

<template>
    <div
        v-if="props.meta"
        class="rounded-0 text-primary dark:text-primary-light bg-slate-300 px-2 dark:bg-slate-800"
    >
        <div
            class="flex flex-wrap items-center justify-between gap-3 print:hidden"
        >
            <div class="inline-flex items-center gap-4">
                <div class="inline-flex items-center gap-2">
                    Show
                    <slot
                        name="per_page"
                        :meta="props.meta"
                        :options="props.perPageOptions"
                        :update-per-page="handlePerPageChange"
                    >
                        <select
                            :value="props.meta.perPage"
                            class="rounded-md border border-slate-400 bg-slate-200 px-2 py-1 text-sm dark:border-slate-600 dark:bg-slate-900"
                            @change="handlePerPageChange"
                        >
                            <option
                                v-for="size in props.perPageOptions"
                                :key="size"
                                :value="size"
                            >
                                {{ size }}
                            </option>
                        </select>
                    </slot>
                </div>
                <code>
                    Showing {{ props.meta.from ?? '0' }} to
                    {{ props.meta.to ?? '0' }} records
                </code>
            </div>

            <nav aria-label="Page navigation" class="print:hidden">
                <ul class="flex items-center gap-2">
                    <Link
                        v-if="props.meta.prevPageUrl"
                        :href="props.meta.prevPageUrl"
                        :only="props.only"
                        :preserve-scroll="props.preserveScroll"
                        :preserve-state="props.preserveState"
                        class="button info text-capitalize flex items-center px-2 py-1 select-none"
                        title="Previous page"
                    >
                        <ChevronsLeftIcon :size="18" />
                        <span class="ml-1">Previous</span>
                    </Link>
                    <span
                        v-else
                        class="flex items-center px-2 py-1 text-body opacity-50"
                    >
                        <ChevronsLeftIcon :size="18" />
                        <span class="ml-1">Previous</span>
                    </span>

                    <span class="px-2 py-1 text-body">
                        Page {{ props.meta.currentPage }}
                    </span>

                    <Link
                        v-if="props.meta.nextPageUrl"
                        :href="props.meta.nextPageUrl"
                        :only="props.only"
                        :preserve-scroll="props.preserveScroll"
                        :preserve-state="props.preserveState"
                        class="button info text-capitalize flex items-center px-2 py-1 select-none"
                        title="Next page"
                    >
                        <span class="mr-1">Next</span>
                        <ChevronsRightIcon :size="18" />
                    </Link>
                    <span
                        v-else
                        class="flex items-center px-2 py-1 text-body opacity-50"
                    >
                        <span class="mr-1">Next</span>
                        <ChevronsRightIcon :size="18" />
                    </span>
                </ul>
            </nav>
        </div>
    </div>
</template>
