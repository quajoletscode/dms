<script lang="ts" setup>
      import { computed, type HTMLAttributes } from 'vue';
      import BaseInput from './BaseInput.vue';

      type InputTypes = 'number' | 'tel';

      interface Props {
            type?: InputTypes;
            inputMode?: 'numeric' | 'decimal' | 'tel' | 'none';
            size?: 'sm' | 'md' | 'lg';
            error?: string;
            helperText?: string;
            class?: HTMLAttributes['class']
            pattern?: HTMLInputElement['pattern']
      }

      const props = withDefaults(defineProps<Props>(), {
            type: 'number',
            inputMode: 'numeric',
            size: 'sm'
      });

      defineOptions({
            inheritAttrs: false
      });

      const model = defineModel<string | number>();

      const sanitizeNumberInput = (value: string | number | null | undefined): string => {
            const normalizedValue = String(value ?? '');
            // Keep digits and a single decimal separator; strip all other characters.
            const cleaned = normalizedValue.replace(/[^\d.]/g, '');
            return cleaned.replace(/(\..*)\./g, '$1');
      };

      const inputModel = computed<string>({
            get() {
                  if (model.value === null || model.value === undefined) {
                        return '';
                  }

                  if (props.type === 'number') {
                        return sanitizeNumberInput(String(model.value));
                  }

                  return String(model.value);
            },
            set(value) {
                  if (props.type === 'number') {
                        const sanitizedValue = sanitizeNumberInput(value);
                        model.value = sanitizedValue === '' ? '' : Number(sanitizedValue);
                        return;
                  }

                  // For tel values, keep the exact input so leading zeros are preserved.
                  model.value = value;
            }
      });
</script>

<template>
      <div class="relative w-full">
            <slot v-if="$slots.leading" name="leading" />
            <BaseInput :pattern="props.pattern" v-bind="$attrs" :error="props.error" v-model="inputModel"
              :type="props.type" v-bind:inputmode="props.inputMode" :class="[{
                  'py-2 rounded-md': props.size === 'sm',
                  'input-md py-3 rounded-lg': props.size === 'md',
                  'input-lg py-3.5 rounded-lg': props.size === 'lg',
            }, props.class]" />
            <slot v-if="$slots.trailing" name="trailing" />
            <small class="text-xs text-gray-600 dark:text-gray-400" v-if="props.helperText">{{ props.helperText }}
            </small>
      </div>
</template>

<style scoped></style>