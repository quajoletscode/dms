<script setup lang="ts">
    import type { InertiaLinkProps } from '@inertiajs/vue3';
    import { Link } from '@inertiajs/vue3';
    import type { LucideProps } from '@lucide/vue';
    import type { FunctionalComponent, HTMLAttributes, StyleValue } from 'vue';
    import { isFullPathActive } from '@/composables/useApp';

    type LucideIcon = FunctionalComponent<LucideProps>;

    interface Props {
        to: NonNullable<InertiaLinkProps['href']>;
        class?: HTMLAttributes['class'];
        style?: StyleValue;
        title?: string;
        name: string;
        icon?: LucideIcon;
        iconSize?: number;
    }

    const props = withDefaults(defineProps<Props>(), {
        name: 'nav item',
        to: '/',
        iconSize: 20,
    });

    const isActive = isFullPathActive(props.to);

    const emits = defineEmits<{
        (e: 'toggle'): void;
    }>();

    const handleClick = () => {
        if (document.body.classList.contains('overflow-y-hidden')) {
            emits('toggle')
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    }
</script>

<template>
    <Link @click="handleClick" :prefetch="'click'" :title="props.title" :href="props.to" :class="[
        props.class,
        {
            'link-active bg-linear-to-l from-primary-light to-primary text-white dark:from-gray-600 dark:to-gray-800':
                isActive,
        },
    ]" :style="props.style" v-bind="$attrs"
      class="nav-menu flex items-center-safe justify-start gap-3 rounded p-2 text-sm! capitalize select-none hover:bg-primary hover:text-white min-[1440px]:text-base dark:hover:bg-gray-600">
        <slot name="icon">
            <component class="flex max-w-10 items-center justify-start" :is="props.icon" :size="props.iconSize" />
        </slot>
        <span>
            {{ props.name }}
        </span>
    </Link>
</template>

<style scoped></style>
