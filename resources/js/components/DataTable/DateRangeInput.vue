<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CalendarDaysIcon } from '@lucide/vue';
import { computed } from 'vue';

interface DateRange {
    start: string | null;
    end: string | null;
}

interface Props {
    modelValue: DateRange;
    label?: string;
    syncWithUrl?: boolean;
    queryParams?: { start: string; end: string };
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    label: 'Date Range',
    syncWithUrl: false,
    queryParams: () => ({ start: 'from', end: 'to' }),
    disabled: false,
});

const emit = defineEmits(['input', 'update:modelValue', 'change']);

const range = computed({
    get() {
        return props.modelValue;
    },
    set(newValue) {
        emit('update:modelValue', newValue);
        emit('input', newValue);
    },
});

/**
 * Modern browser method to trigger the native calendar
 */
const openPicker = (e: MouseEvent) => {
    const target = e.target as HTMLInputElement;

    if ('showPicker' in target) {
        try {
            target.showPicker();
        } catch (error) {
            console.error('showPicker failed', error);
        }
    }
};

const updateDate = (key: 'start' | 'end', val: string) => {
    const newRange = { ...range.value, [key]: val || null };

    if (newRange.start && newRange.end) {
        if (new Date(newRange.start) > new Date(newRange.end)) {
            if (key === 'start') {
                newRange.end = newRange.start;
            } else {
                newRange.start = newRange.end;
            }
        }
    }

    range.value = newRange;
    emit('change', newRange);

    if (props.syncWithUrl) {
        router.get(
            window.location.pathname,
            {
                [props.queryParams.start]: newRange.start,
                [props.queryParams.end]: newRange.end,
            },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }
};
</script>

<template>
    <div class="flex w-full max-w-md flex-col gap-1">
        <label v-if="label" class="px-1 tracking-wider capitalize">
            {{ label }}
        </label>

        <div class="flex items-center gap-2">
            <div class="group input-container relative flex-1">
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3"
                >
                    <CalendarDaysIcon
                        class="h-4 w-4 text-gray-400 transition-colors group-focus-within:text-blue-600"
                    />
                </div>
                <input
                    type="date"
                    :value="range.start"
                    :max="range.end ?? undefined"
                    @click="openPicker"
                    @input="(e: any) => updateDate('start', e.target.value)"
                    class="cursor-pointer pr-3 pl-10 font-medium outline-gray-600 transition-all"
                />
            </div>

            <span class="font-bold text-body">/</span>

            <div class="group input-container relative flex-1">
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3"
                >
                    <CalendarDaysIcon
                        class="h-4 w-4 text-gray-400 transition-colors group-focus-within:text-blue-600"
                    />
                </div>
                <input
                    type="date"
                    :value="range.end"
                    :min="range.start ?? undefined"
                    @click="openPicker"
                    @input="(e: any) => updateDate('end', e.target.value)"
                    class="cursor-pointer pr-3 pl-10 font-medium outline-gray-600 transition-all"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
input::-webkit-calendar-picker-indicator {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 20;
}
</style>
