<script setup lang="ts">
interface Props {
    text: string;
    alignRight?: boolean;
    alignLeft?: boolean;
}
const props = withDefaults(defineProps<Props>(), {
    text: 'divider',
});
</script>

<template>
    <div class="mt-4 flex items-center">
        <!-- <span class="flex items-stretch bg-linear-to-r from-gray-600 to-gray-900 w-full h-[1px]">
    </span> -->
        <span
            class="px-2 font-mono text-nowrap text-gray-400 capitalize dark:text-gray-500"
        >
            {{ props.text }}
        </span>
        <span
            class="flex h-px w-full items-stretch bg-linear-to-r from-gray-400 to-gray-200 dark:from-gray-600 dark:to-gray-900"
        >
        </span>
    </div>
</template>

<style scoped></style>
