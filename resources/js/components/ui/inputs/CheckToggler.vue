<script lang="ts" setup>
  import type { HTMLAttributes } from 'vue';
  import { computed, useTemplateRef } from 'vue';

  interface Props {
    id?: string;
    name?: string;
    label?: string;
    required?: boolean;
    disabled?: boolean;
    class?: HTMLAttributes['class'];
    showStateText?: boolean;
    statusOnText?: string;
    statusOffText?: string;
    title?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    id: () => `switch-${Math.random().toString(36).substring(2, 8)}`,
    label: '',
    required: false,
    disabled: false,
    showStateText: false,
    statusOnText: 'On',
    statusOffText: 'Off',
    title: '',
  });

  defineOptions({
    inheritAttrs: false,
  });

  const model = defineModel<boolean>({
    default: false,
  });

  const isChecked = computed({
    get: () => model.value,
    set: (value: boolean) => {
      model.value = Boolean(value);
    },
  });

  const input = useTemplateRef('input');

  defineExpose({
    input
  });

  const stateLabel = computed(() => (isChecked.value ? props.statusOnText : props.statusOffText));
</script>

<template>
  <div class="input-container">
    <label v-if="props.label" :for="props.id" :title="props.title" class="flex items-center gap-1.25 pb-1.25">
      <span>{{ props.label }}</span>
      <code v-if="props.required" class="inline-flex items-center text-sm">⁕</code>
    </label>

    <label :title="props.title" :for="props.id" class="switch-label"
      :class="[props.class, { 'is-disabled': props.disabled }]">
      <input ref="input" :id="props.id" v-bind="$attrs" v-model="isChecked" :name="props.name || props.id"
        type="checkbox" role="switch" :aria-checked="isChecked" :required="props.required" :disabled="props.disabled"
        class="switch-input" />
      <span class="slider">
        <span class="thumb" />
      </span>
      <span v-if="props.showStateText" class="state-text">{{ stateLabel }}</span>
    </label>
  </div>
</template>

<style scoped>
  .switch-label {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 22px;
    user-select: none;
    cursor: pointer;
  }

  .switch-input {
    position: absolute;
    inline-size: 1px;
    block-size: 1px;
    opacity: 0;
    pointer-events: none;
  }

  .slider {
    position: relative;
    inline-size: 40px;
    block-size: 22px;
    border-radius: 999px;
    background: color-mix(in oklab, var(--color-gray-600) 90%, black 10%);
    box-shadow: inset 0 0 0 1px color-mix(in oklab, var(--color-gray-600) 75%, white 25%);
    transition:
      background-color 0.2s ease,
      box-shadow 0.2s ease,
      transform 0.2s ease;
  }

  .thumb {
    position: absolute;
    inset-block-start: 2px;
    inset-inline-start: 2px;
    inline-size: 18px;
    block-size: 18px;
    border-radius: 999px;
    background-color: var(--color-gray-200);
    box-shadow:
      0 1px 1px rgb(0 0 0 / 0.28),
      0 2px 4px rgb(0 0 0 / 0.16);
    transition:
      transform 0.2s ease,
      background-color 0.2s ease;
  }

  .switch-input:checked+.slider {
    background: var(--color-primary);
    box-shadow:
      inset 0 0 0 1px color-mix(in oklab, var(--color-primary) 75%, white 25%),
      0 0 0 3px color-mix(in oklab, var(--color-primary) 25%, transparent);
  }

  .switch-input:checked+.slider .thumb {
    transform: translateX(18px);
    background-color: var(--color-gray-200);
  }

  .switch-input:focus-visible+.slider {
    outline: 2px solid color-mix(in oklab, var(--color-primary) 70%, white 30%);
    outline-offset: 2px;
  }

  .state-text {
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    color: var(--color-gray-300);
  }

  .switch-input:checked~.state-text {
    color: var(--color-primary-light);
  }

  .is-disabled {
    opacity: 0.55;
    cursor: not-allowed;
  }

  @media (prefers-reduced-motion: reduce) {

    .slider,
    .thumb {
      transition: none;
    }
  }
</style>