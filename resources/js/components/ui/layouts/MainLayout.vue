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

useTheme();

const { addToast } = useToast();
const page = usePage();

watch(
    () => page.props.message,
    (message) => {
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
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <div class="min-h-screen bg-[#edf1f5] text-slate-800">
        <SideNav :is-open="active" @toggle="active = !active" />
        <Header @toggleMenu="active = !active" />
        <main class="w-full flex-1 shrink-0">
            <div class="mx-auto h-full">
                <Breadcrumb class="hidden" />
                <slot />
            </div>
        </main>
    </div>
    <ConfirmDialog />
    <Toaster />
</template>
