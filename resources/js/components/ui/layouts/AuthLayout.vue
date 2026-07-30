<script lang="ts" setup>
      import Toaster from '@/components/Toaster.vue';
      import { useTheme } from '@/composables/useTheme';
      import { useToast } from '@/composables/useToast';
      import { usePage } from '@inertiajs/vue3';
      import { watch } from 'vue';

      useTheme();

      // implement global toast notifications
      const { addToast } = useToast();

      const page = usePage();

      watch(() => page.props.message, (message) => {
            if (message && message.success) {
                  addToast(message.success, 'success');
            }
            if (message && message.error) {
                  addToast(message.error, 'error');
            }
            if (message && message.warning) {
                  addToast(message.warning, 'warning');
            }
            if (message && message.info) {
                  addToast(message.info, 'info');
            }
      }, { immediate: true, deep: true });
</script>

<template>
      <div class="flex items-center justify-center min-h-screen flex-1">
            <slot />
      </div>
      <toaster />
</template>

<style scoped></style>