<script lang="ts" setup>
export type Variant = 'success' | 'neutral' | 'warning' | 'danger' | 'info' | 'accent';

interface Props {
    variant?: Variant;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'neutral',
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset"
        :class="{
            'bg-emerald-100 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-400/20':
                props.variant === 'success',
            'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-400/20':
                props.variant === 'neutral',
            'bg-amber-100 text-amber-700 ring-amber-600/20 dark:bg-amber-900/40 dark:text-amber-300 dark:ring-amber-400/20':
                props.variant === 'warning',
            'bg-red-100 text-red-700 ring-red-600/20 dark:bg-red-900/40 dark:text-red-300 dark:ring-red-400/20': props.variant === 'danger',
            'bg-blue-100 text-blue-700 ring-blue-600/20 dark:bg-blue-900/40 dark:text-blue-300 dark:ring-blue-400/20': props.variant === 'info',
            'bg-violet-100 text-violet-700 ring-violet-600/20 dark:bg-violet-900/40 dark:text-violet-300 dark:ring-violet-400/20':
                props.variant === 'accent',
        }"
    >
        <slot />
    </span>
</template>
