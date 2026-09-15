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
    danger: 'border-rose-500/30 bg-rose-500/10 text-rose-300',
    warning: 'border-amber-500/30 bg-amber-500/10 text-amber-300',
    success: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
    info: 'border-cyan-500/30 bg-cyan-500/10 text-cyan-300',
};

const iconClasses: Record<ReportInsightType, string> = {
    danger: 'text-rose-400',
    warning: 'text-amber-400',
    success: 'text-emerald-400',
    info: 'text-cyan-400',
};
</script>

<template>
    <div class="space-y-2">
        <div
            v-for="(insight, i) in insights"
            :key="i"
            :class="[
                'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm',
                classes[insight.type],
            ]"
        >
            <component
                :is="icons[insight.type]"
                :class="['mt-0.5 h-4 w-4 shrink-0', iconClasses[insight.type]]"
            />
            <span>{{ insight.message }}</span>
        </div>
    </div>
</template>
