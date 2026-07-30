<script setup lang="ts">
    import { ChevronDownIcon, ChevronUpIcon, ChevronsUpDownIcon } from '@lucide/vue';
    import type { ColumnDef } from '@/types';

    const props = defineProps<{
        thead: ColumnDef[];
        sort?: string | null;
        direction?: 'asc' | 'desc' | null;
        /**
         * Pins the last column (typically "Actions") to the right edge of the
         * horizontally-scrolling table so it stays visible on narrow viewports
         * instead of scrolling off-screen with no indication more columns exist.
         * The consuming page must add matching sticky classes to its own last <td>
         * (BaseTable only controls the auto-generated <th> row).
         */
        stickyLastColumn?: boolean;
    }>();

    const emit = defineEmits<{
        sort: [key: string];
    }>();

    const colLabel = (col: ColumnDef) => (typeof col === 'string' ? col : col.label);
    const colKey = (col: ColumnDef) => (typeof col === 'string' ? null : col.key);
    const isSortable = (col: ColumnDef): col is { label: string; key: string } => typeof col !== 'string';

    const colState = (col: ColumnDef) => {
        const k = colKey(col);

        if (!k || props.sort !== k) {
return 'none';
}

        return props.direction ?? 'none';
    };
</script>

<template>
    <div class="rounded-0 relative overflow-x-auto shadow-none">
        <table class="dt-table">
            <thead class="truncate whitespace-nowrap">
                <tr class="text-sm font-medium tracking-wide capitalize">
                    <th
                        v-for="(col, i) in props.thead"
                        :key="i"
                        :title="colLabel(col).toUpperCase()"
                        :class="[
                            'text-left',
                            isSortable(col)
                                ? 'cursor-pointer select-none group/th transition-colors hover:bg-slate-100 dark:hover:bg-slate-800'
                                : '',
                            stickyLastColumn && i === props.thead.length - 1
                                ? 'sticky right-0 z-10 bg-slate-300 dark:bg-slate-800'
                                : '',
                        ]"
                        @click="isSortable(col) ? emit('sort', colKey(col)!) : undefined"
                    >
                        <div class="flex items-center gap-1">
                            <span>{{ colLabel(col) }}</span>
                            <span v-if="isSortable(col)" class="inline-flex flex-shrink-0">
                                <ChevronUpIcon
                                    v-if="colState(col) === 'asc'"
                                    :size="13"
                                    class="text-primary"
                                />
                                <ChevronDownIcon
                                    v-else-if="colState(col) === 'desc'"
                                    :size="13"
                                    class="text-primary"
                                />
                                <ChevronsUpDownIcon
                                    v-else
                                    :size="13"
                                    class="text-slate-400 opacity-0 transition-opacity group-hover/th:opacity-100"
                                />
                            </span>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <slot />
            </tbody>
        </table>
        <div v-if="$slots.empty" class="bg-slate-50 p-6 text-center italic dark:bg-slate-950">
            <slot name="empty" />
        </div>
    </div>
</template>
