<script lang="ts" setup>
import { computed } from 'vue';

const props = defineProps<{
    src: string | null;
    alt: string;
    size?: 'sm' | 'md' | 'lg';
    name?: string;
}>();

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-8 w-8 text-xs';
        case 'lg':
            return 'h-14 w-14 text-lg';
        case 'md':
        default:
            return 'h-10 w-10 text-sm';
    }
});

const initials = computed(() => {
    if (!props.name?.trim()) {
        return '';
    }

    const words = props.name.trim().split(/\s+/).filter(Boolean);

    if (words.length === 1) {
        return words[0].slice(0, 2).toUpperCase();
    }

    return `${words[0][0]}${words[words.length - 1][0]}`.toUpperCase();
});
</script>

<template>
    <img
        v-if="props.src"
        :src="props.src"
        :alt="props.alt"
        :class="[
            'rounded-full object-cover shadow-sm ring-1 ring-black/5',
            sizeClass,
        ]"
    />

    <div
        v-else
        :class="[
            'inline-flex items-center justify-center rounded-full bg-linear-to-br from-slate-600 via-slate-700 to-slate-900 font-semibold tracking-wide text-white uppercase shadow-sm ring-1 ring-black/10 select-none',
            sizeClass,
        ]"
        :aria-label="props.alt"
    >
        {{ initials || '?' }}
    </div>
</template>

<style scoped></style>
