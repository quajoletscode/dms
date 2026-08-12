<script lang="ts" setup>
import { useObjectPageSections, type ObjectPageSectionDef } from '@/composables/useObjectPageSections';

const props = defineProps<{
    sections: ObjectPageSectionDef[];
}>();

const { activeId, scrollTo } = useObjectPageSections(() => props.sections);
</script>

<template>
    <nav
        v-if="sections.length > 1"
        class="flex flex-wrap gap-1 border-b border-slate-200 pb-2 dark:border-slate-700 lg:flex-col lg:gap-0.5 lg:border-b-0 lg:pb-0"
    >
        <button
            v-for="section in sections"
            :key="section.id"
            type="button"
            class="rounded-md px-3 py-1.5 text-left text-sm font-medium transition-colors"
            :class="
                activeId === section.id
                    ? 'bg-primary-light/10 text-primary-light'
                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100'
            "
            @click="scrollTo(section.id)"
        >
            {{ section.label }}
        </button>
    </nav>
</template>
