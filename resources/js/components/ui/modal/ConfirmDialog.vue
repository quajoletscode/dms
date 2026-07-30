<script setup lang="ts">
      import { useConfirm } from '@/composables/useConfirm';
      import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

      const { isOpen, title, message, confirmText, cancelText, respond } = useConfirm();

      const isShaking = ref(false);
      let shakeTimeoutId: number | null = null;

      const triggerShake = (): void => {
            isShaking.value = false;

            if (shakeTimeoutId !== null) {
                  window.clearTimeout(shakeTimeoutId);
                  shakeTimeoutId = null;
            }

            window.requestAnimationFrame(() => {
                  isShaking.value = true;

                  shakeTimeoutId = window.setTimeout(() => {
                        isShaking.value = false;
                        shakeTimeoutId = null;
                  }, 350);
            });
      };

      const handleKeydown = (event: KeyboardEvent): void => {
            if (event.key === 'Escape' && isOpen.value) {
                  respond(false);
            }
      };

      onMounted(() => {
            window.addEventListener('keydown', handleKeydown);
      });

      onBeforeUnmount(() => {
            window.removeEventListener('keydown', handleKeydown);

            if (shakeTimeoutId !== null) {
                  window.clearTimeout(shakeTimeoutId);
            }
      });

      watch(isOpen, (newVal) => {
            if (newVal) {
                  window.requestAnimationFrame(() => {
                        isShaking.value = true;

                        shakeTimeoutId = window.setTimeout(() => {
                              isShaking.value = false;
                              shakeTimeoutId = null;
                        }, 350);
                  });
                  document.body.style.overflow = 'hidden';
            } else {
                  document.body.style.overflow = '';
            }
      });

      const bubbleAnimateClass = computed(() => (isShaking.value ? 'confirm-dialog-shake' : ''));
</script>

<template>
      <div @click.self="triggerShake" v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/55 backdrop-blur-xs/">
            <div
              class="w-full max-w-sm p-6 bg-body border dark:border-gray-700 border-gray-300 backdrop-blur-sm dark:backdrop-blur-none rounded-xl shadow-lg space-y-4"
              :class="bubbleAnimateClass">
                  <h3 class="text-lg font-semibold">
                        {{ title }}
                  </h3>

                  <p class="">
                        {{ message }}
                  </p>

                  <div class="flex flex-wrap w-full  sm:justify-end gap-3 pt-2">
                        <button type="button" @click="respond(false)"
                          class="w-full sm:w-auto px-4 py-2 text-sm outline outline-slate-300 bg-slate-200 hover:bg-slate-300 font-medium text-gray-700 rounded-lg transition-colors">
                              {{ cancelText }}
                        </button>

                        <button type="button" @click="respond(true)"
                          class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-primary-light rounded-lg hover:bg-primary transition-colors">
                              {{ confirmText }}
                        </button>
                  </div>
            </div>
      </div>
</template>

<style scoped>
      @keyframes confirm-dialog-shake {

            0%,
            100% {
                  transform: translateX(0);
            }

            20% {
                  transform: translateX(-8px);
            }

            40% {
                  transform: translateX(8px);
            }

            60% {
                  transform: translateX(-6px);
            }

            80% {
                  transform: translateX(6px);
            }
      }

      .confirm-dialog-shake {
            animation: confirm-dialog-shake 0.35s ease-in-out;
      }

      @media (prefers-reduced-motion: reduce) {
            .confirm-dialog-shake {
                  animation: none;
            }
      }
</style>