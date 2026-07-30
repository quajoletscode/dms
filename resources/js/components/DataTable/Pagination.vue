<script setup lang="ts">
import type { SimplePaginationMeta } from '@/types';
import SimplePagination from './SimplePagination.vue';

const props = defineProps<{
    meta?: SimplePaginationMeta;
    routeUrl: string;
    only?: string[];
}>();
</script>

<template>
    <SimplePagination
        :meta="props.meta"
        :only="props.only"
        :route-url="props.routeUrl"
    />
</template>
