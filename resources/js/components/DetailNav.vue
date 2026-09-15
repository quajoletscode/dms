<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon } from '@lucide/vue';
import { useAdjacentNavigation } from '@/composables/useAdjacentNavigation';

const props = defineProps<{
    prevUrl: string | null;
    nextUrl: string | null;
}>();

useAdjacentNavigation(
    () => props.prevUrl,
    () => props.nextUrl,
);

const buttonClass =
    'inline-flex size-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition-colors hover:border-primary-light hover:text-primary-light dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400';
const disabledClass =
    'inline-flex size-9 items-center justify-center rounded-lg border border-slate-100 text-slate-300 dark:border-slate-800 dark:text-slate-700';
</script>

<template>
    <div class="flex items-center gap-2">
        <Link
            v-if="prevUrl"
            :href="prevUrl"
            :class="buttonClass"
            title="Previous (←)"
            prefetch="hover"
        >
            <ChevronLeftIcon :size="18" />
        </Link>
        <span v-else :class="disabledClass"
            ><ChevronLeftIcon :size="18"
        /></span>

        <Link
            v-if="nextUrl"
            :href="nextUrl"
            :class="buttonClass"
            title="Next (→)"
            prefetch="hover"
        >
            <ChevronRightIcon :size="18" />
        </Link>
        <span v-else :class="disabledClass"
            ><ChevronRightIcon :size="18"
        /></span>
    </div>
</template>
