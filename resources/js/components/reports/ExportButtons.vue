<script setup lang="ts">
import { FileSpreadsheetIcon, FileTextIcon } from '@lucide/vue';

defineProps<{
    csvUrl: string;
    pdfUrl: string;
}>();
</script>

<template>
    <div class="flex items-center gap-2 print:hidden">
        <a
            :href="csvUrl"
            download
            title="Download CSV"
            class="flex items-center justify-center gap-2 rounded-md border border-gray-300 bg-white p-2.5 text-sm text-body select-none active:bg-gray-300 dark:border-gray-700 dark:bg-slate-800 dark:active:bg-gray-700"
        >
            <FileSpreadsheetIcon :size="18" />
            CSV
        </a>
        <a
            :href="pdfUrl"
            download
            title="Download PDF"
            class="flex items-center justify-center gap-2 rounded-md border border-gray-300 bg-white p-2.5 text-sm text-body select-none active:bg-gray-300 dark:border-gray-700 dark:bg-slate-800 dark:active:bg-gray-700"
        >
            <FileTextIcon :size="18" />
            PDF
        </a>
    </div>
</template>

<style scoped></style>
