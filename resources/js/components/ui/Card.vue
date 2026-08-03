<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';

interface Props {
    class?: HTMLAttributes['class'];
    padding?: 'none' | 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    padding: 'md',
});
</script>

<template>
    <div
        class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
        :class="[
            props.class,
            {
                'p-0': props.padding === 'none',
                'p-4': props.padding === 'sm',
                'p-6': props.padding === 'md',
                'p-8': props.padding === 'lg',
            },
        ]"
    >
        <div v-if="$slots.header" class="mb-4 flex items-center justify-between gap-3 border-b border-slate-200 pb-4 dark:border-slate-700">
            <slot name="header" />
        </div>

        <slot />

        <div v-if="$slots.footer" class="mt-4 flex items-center justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
            <slot name="footer" />
        </div>
    </div>
</template>
