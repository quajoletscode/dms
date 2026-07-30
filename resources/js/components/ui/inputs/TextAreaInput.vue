<script lang="ts" setup>
  import type { HTMLAttributes } from 'vue';
  import { computed, ref, watch } from 'vue';

  interface Props {
    id?: string;
    name?: string;
    label?: string;
    required?: boolean;
    error?: string;
    helperText?: string;
    size?: 'sm' | 'md' | 'lg';
    characterCount?: boolean;
    countLimit?: number;
    maxLength?: string | number;
    minLength?: string | number;
    class?: HTMLAttributes['class'];
  }

  const props = withDefaults(defineProps<Props>(), {
    id: () => `input-${Math.random().toString(36).substring(7)}`,
    required: false,
    size: 'sm',
    characterCount: false,
  });

  defineOptions({
    inheritAttrs: false,
  });

  const model = defineModel<string>({
    default: '',
  });

  const localValue = ref(model.value ?? '');

  const effectiveMaxLength = computed<number | undefined>(() => {
    if (props.maxLength !== undefined) {
      return Number(props.maxLength);
    }

    if (props.countLimit !== undefined) {
      return props.countLimit;
    }

    return undefined;
  });

  watch(
    () => model.value,
    (nextValue) => {
      const normalized = nextValue ?? '';

      if (normalized !== localValue.value) {
        localValue.value = normalized;
      }
    },
  );

  const textareaValue = computed({
    get: () => localValue.value,
    set: (nextValue: string) => {
      let normalizedValue = nextValue;

      if (
        effectiveMaxLength.value !== undefined &&
        nextValue.length > effectiveMaxLength.value
      ) {
        normalizedValue = nextValue.substring(0, effectiveMaxLength.value);
      }

      localValue.value = normalizedValue;
      model.value = normalizedValue;
    },
  });

  const currentLength = computed(() => textareaValue.value.length);
</script>

<template>
  <div class="input-container">
    <label v-if="label" :for="id" class="flex items-center gap-1.25 pb-1.25">
                  <span>{{ label }}</span>
                  <code v-if="required" class="inline-flex items-center text-sm">⁕</code>
            </label>

    <textarea :id="props.id || props.name" :name="props.name || props.id" v-model="textareaValue"
      :required="props.required" :maxlength="effectiveMaxLength" :minlength="props.minLength" v-bind="$attrs" :class="[
        'w-full px-1.25 pr-8 outline-[1.5px] bg-white dark:bg-inherit focus:outline-primary-light disabled:pointer-events-none! disabled:touch-none! hover:outline-primary-light transition-all',
        {
          'py-2 rounded-md': props.size === 'sm',
          'input-md py-3 rounded-lg': props.size === 'md',
          'input-lg py-3.5 rounded-lg': props.size === 'lg',
          'outline-red-500 hover:outline-red-500 focus:outline-primary': props.error,
          'pl-11': $slots.leading || $slots.icon,
        },
        props.class,
      ]" />


    <p v-if="props.error" class="mt-1 animate-in fade-in slide-in-from-top-1 text-sm text-red-500">
      {{ props.error }}
    </p>

    <small v-if="props.helperText" class="text-xs text-gray-600 dark:text-gray-400">
      {{ props.helperText }}
    </small>

    <p v-if="props.characterCount" class="text-end text-xs">
      <span :class="{
        'text-red-500':
          effectiveMaxLength !== undefined &&
          currentLength >= effectiveMaxLength,
      }">
        {{ currentLength }}
      </span>
      <span v-if="effectiveMaxLength"> / {{ effectiveMaxLength }} </span>
    </p>
  </div>
</template>

<style scoped></style>