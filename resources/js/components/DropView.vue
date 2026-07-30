<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { ref } from 'vue';

interface Props {
    left?: boolean;
    right?: boolean;
    class?: HTMLAttributes['class'];
    open?: boolean;
}
const props = withDefaults(defineProps<Props>(), {
    right: true,
});

const isFocused = ref(false);
</script>

<template>
    <div
        :="{ open: props.open }"
        class="relative inline-block"
        @click.self="isFocused = true"
        @mouseenter.self="isFocused = true"
        @mouseleave.self="isFocused = false"
    >
        <div class="flex items-center">
            <slot name="trigger" />
        </div>
        <Transition name="fade" mode="out-in">
            <div
                v-if="isFocused || props.open"
                v-bind="{ right: props.right, left: props.left }"
                class="absolute z-150 inline-block min-w-30 rounded-md border border-slate-300 bg-body p-2 shadow-lg dark:border-gray-700"
                :class="[{ 'right-0': right, 'left-0': left }, props.class]"
            >
                <slot> </slot>
            </div>
        </Transition>
    </div>
</template>

<style scoped></style>
