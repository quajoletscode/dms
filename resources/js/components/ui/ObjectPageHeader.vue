<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@lucide/vue';
import Badge, { type Variant } from '@/components/ui/Badge.vue';

defineProps<{
    title: string;
    subtitle?: string;
    backHref?: string;
    status?: { label: string; variant: Variant };
}>();
</script>

<template>
    <div
        class="sticky top-14 z-20 -mx-4 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 bg-body px-4 py-3 dark:border-slate-700"
    >
        <div class="flex items-center gap-3">
            <Link v-if="backHref" :href="backHref" class="text-slate-400 hover:text-primary-light" title="Back">
                <ArrowLeftIcon :size="20" />
            </Link>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ title }}</h1>
                    <Badge v-if="status" :variant="status.variant">{{ status.label }}</Badge>
                </div>
                <p v-if="subtitle" class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <slot name="nav" />
            <slot name="actions" />
        </div>
    </div>
</template>
