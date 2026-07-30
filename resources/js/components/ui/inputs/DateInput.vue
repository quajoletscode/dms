<script lang="ts" setup>
      import type { HTMLAttributes } from 'vue';
      import BaseInput from './BaseInput.vue';


      interface Props {
            type: 'date' | 'time' | 'datetime-local';
            inputMode?: 'date' | 'time' | 'datetime-local';
            size?: 'sm' | 'md' | 'lg';
            error?: string;
            helperText?: string;
            class?: HTMLAttributes['class'],
            min?: string;
            max?: string;
      }

      const props = withDefaults(defineProps<Props>(), {
            inputMode: 'date',
            size: 'sm'
      });

      defineOptions({
            inheritAttrs: false
      });

      const model = defineModel<string | number>();

</script>

<template>
      <div class="relative w-full">
            <slot v-if="$slots.leading" name="leading" />
            <BaseInput v-bind="$attrs" :error="props.error" v-model="model" :type="props.type"
              v-bind:inputmode="props.inputMode" :class="[{
                  'py-2 rounded-md': props.size === 'sm',
                  'input-md py-3 rounded-lg': props.size === 'md',
                  'input-lg py-3.5 rounded-lg': props.size === 'lg',
            }, props.class]" :min="props.min" :max="props.max" />
            <slot v-if="$slots.trailing" name="trailing" />
            <small class="text-xs text-gray-600 dark:text-gray-400"
              v-if="props.helperText">{{ props.helperText }}</small>
      </div>
</template>

<style scoped></style>