<script lang="ts" setup>
      import { computed, onMounted, onUnmounted, useTemplateRef, watch } from 'vue';

      const props = withDefaults(defineProps<{
            title?: string;
            isOpen: boolean;
            static?: boolean;
            keyboard?: boolean;
            postion?: 'center' | 'top' | 'bottom' | undefined;
            size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | 'full';
      }>(), {
            title: 'Modal Title',
            isOpen: false,
            postion: 'center',
            size: 'sm',
            static: false,
            keyboard: true
      });

      const emit = defineEmits(['close']);

      const handleKeydown = (e: KeyboardEvent) => {
            if (props.isOpen && props.keyboard && e.key === 'Escape') emit('close');
      }

      const closeModal = () => {
            if (!props.static) emit('close');
      }

      onMounted(() => {
            window.addEventListener('keydown', handleKeydown);
      });

      onUnmounted(() => {
            window.removeEventListener('keydown', handleKeydown);
      });

      watch(() => props.isOpen, (isShown) => {
            if (isShown) document.body.style.overflow = 'hidden';
            else document.body.style.overflow = '';
      });

      // Computed class for alignment
      const alignmentClasses = computed(() => ({
            'items-center': props.postion === 'center' || (props.postion !== 'top' && props.postion !== 'bottom'),
            'items-start': props.postion === 'top',
            'items-end': props.postion === 'bottom'
      }));

      // Computed class for sizes
      const widthClasses = computed(() => ({
            'max-w-md': props.size === 'sm' || (props.size !== 'md' && props.size !== 'lg' && props.size !== 'xl' && props.size !== '2xl' && props.size !== '3xl' && props.size !== 'full'), // Default size
            'max-w-xl': props.size === 'md',
            'max-w-2xl': props.size === 'lg',
            'max-w-4xl': props.size === 'xl',
            'max-w-5xl': props.size === '2xl',
            'max-w-7xl': props.size === '3xl',
            'max-w-[96vw]': props.size === 'full'
      }));

      const modalContainer = useTemplateRef('modalContainer');

      // 2. Logic to find and focus the input inside the slot
      const focusFirstInput = () => {
            if (!modalContainer.value) return;

            // Look for an element with the 'autofocus' attribute first
            const autofocusEl = modalContainer.value.querySelector<HTMLElement>('[autofocus]');
            if (autofocusEl) {
                  autofocusEl.focus();
                  return;
            }

            // Fallback: Find the first input, textarea, or select
            const firstInput = modalContainer.value.querySelector<HTMLElement>(
                  'input:not([type="hidden"],[type="file"]), textarea, select, [tabindex]:not([tabindex="-1"])'
            );
            firstInput?.focus();
      };
</script>

<template>
      <Teleport to="body" defer>
            <Transition name="modal" mode="in-out" @after-enter="focusFirstInput">
                  <div ref="modalContainer" v-if="props.isOpen" @click.self="closeModal"
                    :class="['modal-layer fixed inset-0 z-50 flex justify-center bg-[rgba(0,0,0,0.65)] focus-visible:outline-0', alignmentClasses]"
                    role="dialog" aria-modal="true" tabindex="-1">
                        <div
                          :class="['modal bg-gray-100 dark:bg-gray-900 text-body border border-gray-300 dark:border-gray-700 rounded-lg shadow-lg overflow-visible max-h-[calc(100dvh-2rem)] w-full m-4 px-6 relative flex flex-col', widthClasses]"
                          @click.stop>
                              <div class="flex justify-between items-center pb-3 shrink-0 bg-body z-10 py-6">
                                    <h3 class="text-xl font-semibold">{{ props.title }}</h3>
                                    <button title="Close" @click="emit('close')" type="button"
                                      class="hover:text-red-600 cursor-pointer">
                                          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M6 18L18 6M6 6l12 12" />
                                          </svg>
                                    </button>
                              </div>

                              <div class="relative px-2 py-6 overflow-y-auto overflow-x-visible min-h-0 flex-1 w-full">
                                    <slot></slot>
                              </div>

                              <div v-if="$slots.footer"
                                class="py-3 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2 shrink-0">
                                    <slot name="footer"></slot>
                              </div>
                        </div>
                  </div>
            </Transition>
      </Teleport>
</template>

<style scoped></style>