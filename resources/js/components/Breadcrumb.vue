<script lang="ts" setup>
    import { Link, usePage } from '@inertiajs/vue3';
    import type { HTMLAttributes } from 'vue';
    import { ref, watch } from 'vue';
    import type { Breadcrumb } from '@/types';

    const page = usePage();

    const props = defineProps<{
        class?: HTMLAttributes['class'];
        library?: string;
        acccounts?: string;
        superAdmin?: string;
    }>();

    const items = ref<Breadcrumb[]>(
        Array.isArray(page.props.breadcrumb)
            ? (page.props.breadcrumb as Breadcrumb[])
            : [],
    );

    const isLast = (index: number): boolean => {
        return index === items.value.length - 1;
    };

    watch(
        () => page.props.breadcrumb,
        (breadcrumb) => {
            if (breadcrumb && Array.isArray(breadcrumb)) {
                items.value = breadcrumb as Breadcrumb[];
            } else {
                items.value = [];
            }
        },
    );
</script>

<template>
    <nav aria-label="breadcrumb" :class="props.class">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item flex" v-if="page.props.auth.station">
                <span class="font-semibold">
                    {{ page.props.auth.station.name + '(' + page.props.auth.station.code + ')' }}
                </span>
            </li>
            <li class="breadcrumb-item flex lg:hidden" v-else>
                <span class="font-semibold">
                    {{ page.props.auth.company }}
                </span>
            </li>
            <li v-for="(breadcrumb, i) in items" :key="i" class="breadcrumb-item">
                <Link :prefetch="'hover'" :class="{
                    'font-semibold': !isLast(i).valueOf()
                }" :title="breadcrumb.label" v-if="!isLast(i) && breadcrumb.url" :href="breadcrumb.url">
                    {{ breadcrumb.label }}
                </Link>
                <span :title="breadcrumb.label" v-else>{{
                    breadcrumb.label
                }}</span>
            </li>
        </ol>
    </nav>
</template>

<style scoped></style>
