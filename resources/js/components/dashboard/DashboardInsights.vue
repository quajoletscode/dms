<script lang="ts" setup>
import { AlertTriangle, CheckCircle2, Info, TriangleAlert } from '@lucide/vue';
import type { ReportInsight, ReportInsightType } from '@/types/reports';

defineProps<{
    insights: ReportInsight[];
}>();

const icons: Record<ReportInsightType, any> = {
    danger: TriangleAlert,
    warning: AlertTriangle,
    success: CheckCircle2,
    info: Info,
};

const classes: Record<ReportInsightType, string> = {
    danger: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300',
    warning: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-300',
    info: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/40 dark:bg-blue-950/20 dark:text-blue-300',
};

const iconClasses: Record<ReportInsightType, string> = {
    danger: 'text-red-500 dark:text-red-400',
    warning: 'text-amber-500 dark:text-amber-400',
    success: 'text-emerald-500 dark:text-emerald-400',
    info: 'text-blue-500 dark:text-blue-400',
};
</script>

<template>
    <div v-if="insights.length" class="grid gap-2 md:grid-cols-2">
        <div
            v-for="(insight, i) in insights"
            :key="i"
            :class="['flex items-start gap-3 rounded-lg border px-4 py-3 text-sm', classes[insight.type]]"
        >
            <component :is="icons[insight.type]" :class="['mt-0.5 h-4 w-4 shrink-0', iconClasses[insight.type]]" />
            <span>{{ insight.message }}</span>
        </div>
    </div>
</template>
