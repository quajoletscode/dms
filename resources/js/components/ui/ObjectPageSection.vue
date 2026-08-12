<script lang="ts" setup>
import Card from '@/components/ui/Card.vue';

defineProps<{
    id: string;
    title?: string;
}>();
</script>

<template>
    <section :id="id" class="scroll-mt-32">
        <Card>
            <template v-if="title || $slots.header" #header>
                <slot name="header">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h2>
                </slot>
            </template>

            <slot />

            <template v-if="$slots.footer" #footer>
                <slot name="footer" />
            </template>
        </Card>
    </section>
</template>
