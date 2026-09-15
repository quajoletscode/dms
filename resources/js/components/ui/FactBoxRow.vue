<script lang="ts" setup>
defineProps<{
    label: string;
}>();
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-sm">
        <dt class="min-w-0 truncate text-slate-500 dark:text-slate-400">
            {{ label }}
        </dt>
        <dd
            class="min-w-0 text-right font-medium text-slate-900 dark:text-slate-100"
        >
            <slot />
        </dd>
    </div>
</template>
