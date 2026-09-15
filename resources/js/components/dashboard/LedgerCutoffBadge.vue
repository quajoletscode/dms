<script lang="ts" setup>
import { CalendarClock } from '@lucide/vue';
import { computed } from 'vue';
import { toHumanDate } from '@/composables/useDate';

const props = defineProps<{
    cutoff: string | null;
}>();

const label = computed(() => (props.cutoff ? toHumanDate(props.cutoff) : null));
</script>

<template>
    <div
        v-if="label"
        class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
        :title="`Transactions dated on or before ${label} are folded into opening balances — edits to them are blocked.`"
    >
        <CalendarClock class="h-4 w-4 text-slate-400" />
        Ledger cutoff: {{ label }}
    </div>
</template>
