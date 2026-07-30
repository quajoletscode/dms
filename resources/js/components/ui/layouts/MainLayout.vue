<script lang="ts" setup>
      import { ref, watch } from 'vue';
      import { useTheme } from '@/composables/useTheme';
      import SideNav from './SideNav.vue';
      import ConfirmDialog from '../modal/ConfirmDialog.vue';
      import Header from './Header.vue';
      import Breadcrumb from '@/components/Breadcrumb.vue';
      import { useToast } from '@/composables/useToast';
      import Toaster from '@/components/Toaster.vue';
      import { usePage } from '@inertiajs/vue3';

      const active = ref(false);

      useTheme();//triggers theme initialization on app load, this ensures that the theme is set correctly based on user preference or system settings when the application starts.

      // implement global toast notifications
      const { addToast } = useToast();

      const page = usePage();

      watch(() => page.props.message, (message) => {
            if (message && message.success) {
                  addToast(message.success, 'success');
            }
            if (message && message.error) {
                  addToast(message.error, 'error', 9000);
            }
            if (message && message.warning) {
                  addToast(message.warning, 'warning');
            }
            if (message && message.info) {
                  addToast(message.info, 'info');
            }
      }, { immediate: true, deep: true });

      // // watch for auth user changes to handle auto logout when session expires or user logs out in another tab
      // watch(() => page.props.auth.user, (user) => {
      //       if (!user) {
      //             // redirect to login page if user becomes null (logged out)
      //             window.location.href = '/login';
      //       }
      // }, { immediate: true, deep: true });

</script>

<template>
      <div>
            <SideNav :is-open="active" @toggle="active = !active" />
            <Header @toggleMenu="active = !active" />
            <main class="lg:ml-80 flex-1 shrink-0">
                  <div class="max-w-500 mx-auto h-full p-4">
                        <Breadcrumb class="my-4" />
                        <slot />
                  </div>
            </main>
      </div>
      <ConfirmDialog />
      <Toaster />
</template>

<style scoped></style>