<script setup lang="ts">
import { CheckCircle, AlertCircle, Info, AlertTriangle, X } from '@lucide/vue';
import { useToast } from '../composables/useToast';

const { toasts, removeToast } = useToast();

const icons = {
    success: CheckCircle,
    error: AlertCircle,
    info: Info,
    warning: AlertTriangle,
};

const styles = {
    success:
        'border-emerald-500/20 bg-emerald-200 text-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-400',
    error: 'border-rose-500/20 bg-rose-200 text-rose-900 dark:bg-rose-950/30 dark:text-rose-400',
    info: 'border-blue-500/20 bg-blue-200 text-blue-900 dark:bg-blue-950/30 dark:text-blue-400',
    warning:
        'border-amber-500/20 bg-amber-200 text-amber-900 dark:bg-amber-950/30 dark:text-amber-400',
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="toasts.length"
            class="fixed top-4 right-4 z-55 flex w-full max-w-sm flex-col gap-3"
        >
            <TransitionGroup
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform translate-x-10 opacity-0"
                enter-to-class="transform translate-x-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :class="[
                        'flex items-center gap-3 rounded-xl border p-4 shadow backdrop-blur-md',
                        styles[toast.type],
                    ]"
                >
                    <component
                        :is="icons[toast.type]"
                        class="h-5 w-5 shrink-0"
                    />

                    <p
                        class="flex-1 text-sm font-medium lowercase first-letter:uppercase"
                    >
                        {{ toast.message }}
                    </p>

                    <button
                        @click="removeToast(toast.id)"
                        class="rounded-lg p-1 transition-colors hover:bg-black/5 dark:hover:bg-white/10"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
