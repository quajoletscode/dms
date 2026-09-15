<script lang="ts" setup>
defineProps<{
    title: string;
}>();
</script>

<template>
    <aside
        class="h-fit space-y-3 rounded-lg border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-900/40"
    >
        <h2
            class="text-xs font-semibold tracking-wide text-slate-500 uppercase dark:text-slate-400"
        >
            {{ title }}
        </h2>
        <dl class="space-y-2.5">
            <slot />
        </dl>
    </aside>
</template>
