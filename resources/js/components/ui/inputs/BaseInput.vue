<script lang="ts" setup>
      import { ref, useTemplateRef, type HTMLAttributes } from 'vue';

      interface InputProps {
            required?: boolean;
            label?: string;
            type?: string;
            id?: string;
            name?: string;
            class?: HTMLAttributes['class'];
            style?: HTMLAttributes['style'];
            error?: string;
      }

      const props = withDefaults(defineProps<InputProps>(), {
            required: false,
            label: '',
            type: 'text',
            id: () => `input-${Math.random().toString(36).substring(7)}`,
      });

      // Modern way to handle v-model
      const model = defineModel<string | number>();

      const inputElement = useTemplateRef('inputElement');

      defineExpose({
            inputElement,
      });

      defineOptions({
            inheritAttrs: false
      });
</script>

<template>
      <div class="input-container">
            <label v-if="label" :for="id" class="flex items-center gap-1.25 pb-1.25">
                  <span>{{ label }}</span>
                  <code v-if="required" class="inline-flex items-center text-sm">⁕</code>
            </label>
            <slot v-if="$slots.leading" name="leading" />

            <input ref="inputElement" v-model="model" v-bind="$attrs" :id="id" :type="type" :name="name ?? id"
              :required="required" :class="[
                  'px-1.25 outline-[1.5px] bg-white dark:bg-inherit focus:outline-primary-light disabled:pointer-events-none! disabled:touch-none! hover:outline-primary-light transition-all',
                  { 'outline-red-500  hover:outline-red-500 focus:outline-primary': props.error },
                  props.class
            ]" :style="style" />

            <slot v-if="$slots.trailing" name="trailing" />
      </div>
      <p v-if="error" class="text-sm text-red-500 mt-1 animate-in fade-in slide-in-from-top-1">
            {{ props.error }}
      </p>
</template>