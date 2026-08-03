<script lang="ts" setup>
defineProps<{
    label: string;
}>();
</script>

<template>
    <div>
        <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400">{{ label }}</dt>
        <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100"><slot /></dd>
    </div>
</template>
