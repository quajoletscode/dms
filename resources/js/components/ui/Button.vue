<script setup lang="ts">
  import { HTMLAttributes } from 'vue';

  type ButtonTypes = 'button' | 'submit' | 'reset' | undefined;

  type Variants = 'light' | 'primary' | 'none' | 'blue' | 'danger' | 'red' | 'warning' | 'green' | 'info' | 'ghost' | undefined | null;

  interface Props {
    variant?: Variants,
    type?: ButtonTypes,
    name?: string,
    id?: string,
    style?: HTMLAttributes['style'],
    class?: HTMLAttributes['class'],
    attr?: HTMLAttributes,
    disabled?: boolean,
    title?: HTMLAttributes['title']
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    type: 'button',
    disabled: false
  });

</script>

<template>
  <button :attr :title="props.title ? props.title : undefined" :disabled="props.disabled" :type="props.type"
    :style="props.style"
    class="rounded-md px-3 py-2.5 uppercase text-base focus-visible:outline-0 flex items-center justify-center gap-2.5 select-none"
    :class="[props.class, {
      'bg-primary-light active:bg-primary text-white': props.variant === 'primary',
      'bg-blue-600 active:bg-blue-800 text-white': props.variant === 'blue',
      'bg-green-700 active:bg-green-900 text-white': props.variant === 'green',
      'bg-red-600 active:bg-red-800 text-white': props.variant === 'red',
      'bg-red-500/90 active:bg-red-700 text-white': props.variant === 'danger',
      'bg-amber-600 active:bg-amber-800 text-white': props.variant === 'warning',
      'bg-blue-400 active:bg-blue-500 text-white': props.variant === 'info',
      'bg-gray-200 active:bg-gray-400 text-black': props.variant === 'light',
      'dark:bg-slate-800 bg-white border border-gray-300 dark:border-gray-700 active:bg-gray-300 dark:active:bg-gray-700 text-body': props.variant === 'ghost',
      'opacity-50 cursor-not-allowed': props.disabled,
    }]">
    <slot>
      button
    </slot>
  </button>
</template>

<style scoped></style>