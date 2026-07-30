<script lang="ts" setup>
      import { computed, ref, type HTMLAttributes } from 'vue';
      import BaseInput from './BaseInput.vue';
      import { EyeIcon, EyeOffIcon } from '@lucide/vue';

      type InputTypes = 'text' | 'password';

      interface Props {
            type?: InputTypes;
            size?: 'sm' | 'md' | 'lg';
            error?: string;
            class?: HTMLAttributes['class']
      }

      const props = withDefaults(defineProps<Props>(), {
            type: 'password',
            size: 'sm'
      });

      const showPassword = ref(false);
      const inputType = ref<InputTypes>(props.type);
      const baseInputRef = ref<InstanceType<typeof BaseInput> | null>(null);
      const canToggleVisibility = computed(() => props.type === 'password');


      const togglePasswordVisibility = () => {
            showPassword.value = !showPassword.value;

            const nextType: InputTypes = showPassword.value ? 'text' : 'password';

            inputType.value = nextType;

            if (baseInputRef.value?.inputElement) {
                  baseInputRef.value.inputElement.type = nextType;
            }
      };

      defineOptions({
            inheritAttrs: false
      });

      const model = defineModel<string | number>();

</script>

<template>
      <div class="relative w-full">
            <BaseInput ref="baseInputRef" v-bind="$attrs" :error="props.error" v-model="model" :type="inputType" :class="[{
                  'py-2 rounded-md': props.size === 'sm',
                  'input-md py-3 rounded-lg': props.size === 'md',
                  'input-lg py-3.5 rounded-lg': props.size === 'lg',
            }, props.class]">
                  <template #trailing>
                        <div
                          class="absolute top-3/4 -translate-y-2/3 right-2 flex items-center select-none cursor-pointer transition-colors bg-none"
                          v-if="canToggleVisibility" @click="togglePasswordVisibility"
                          :title="showPassword ? 'Hide Password' : 'Show Password'">
                              <EyeOffIcon :size="20" v-if="showPassword" />
                              <EyeIcon :size="20" v-else />
                        </div>
                  </template>
            </BaseInput>
      </div>
</template>

<style scoped></style>