<script lang="ts" setup>
import { ChevronDownIcon } from '@lucide/vue';
import { ref } from 'vue';
import Card from '@/components/ui/Card.vue';

const props = withDefaults(
    defineProps<{
        id: string;
        title?: string;
        collapsible?: boolean;
    }>(),
    {
        collapsible: false,
    },
);

const expanded = ref(true);
</script>

<template>
    <section
        :id="id"
        class="scroll-mt-32"
        @objectpage-expand="expanded = true"
    >
        <Card>
            <template v-if="title || $slots.header" #header>
                <button
                    v-if="props.collapsible"
                    type="button"
                    class="flex w-full items-center justify-between gap-2 text-left"
                    :aria-expanded="expanded"
                    :aria-controls="`${id}-body`"
                    @click="expanded = !expanded"
                >
                    <slot name="header">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h2>
                    </slot>
                    <ChevronDownIcon
                        :size="16"
                        class="shrink-0 text-slate-400 transition-transform"
                        :class="{ '-rotate-90': !expanded }"
                    />
                </button>
                <slot v-else name="header">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h2>
                </slot>
            </template>

            <div :id="`${id}-body`" v-show="!props.collapsible || expanded">
                <slot />
            </div>

            <template v-if="$slots.footer" #footer>
                <slot name="footer" />
            </template>
        </Card>
    </section>
</template>
