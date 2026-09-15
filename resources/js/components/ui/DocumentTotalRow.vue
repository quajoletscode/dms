<script lang="ts" setup>
type Tone = 'default' | 'danger' | 'warning';

withDefaults(
    defineProps<{
        label: string;
        emphasized?: boolean;
        tone?: Tone;
    }>(),
    {
        emphasized: false,
        tone: 'default',
    },
);

const toneClass: Record<Tone, string> = {
    default: '',
    danger: 'text-red-600 dark:text-red-400',
    warning: 'text-amber-600 dark:text-amber-400',
};
</script>

<template>
    <div
        class="flex items-center justify-between gap-4"
        :class="
            emphasized
                ? 'mt-2 border-t border-slate-200 pt-2 text-base font-semibold text-slate-900 dark:border-slate-700 dark:text-slate-100'
                : 'text-sm text-slate-500 dark:text-slate-400'
        "
    >
        <dt>{{ label }}</dt>
        <dd
            class="tabular-nums"
            :class="[
                !emphasized && 'font-medium',
                tone !== 'default'
                    ? toneClass[tone]
                    : !emphasized && 'text-slate-900 dark:text-slate-100',
            ]"
        >
            <slot />
        </dd>
    </div>
</template>
