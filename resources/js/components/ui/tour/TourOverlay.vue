<script setup lang="ts">
import { ChevronLeftIcon, ChevronRightIcon, XIcon } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useTour } from '@/composables/useTour';

const { isActive, steps, stepIndex, next, back, skip } = useTour();

const PADDING = 8;

const targetRect = ref<DOMRect | null>(null);

const currentStep = computed(() => steps.value[stepIndex.value] ?? null);
const isFirst = computed(() => stepIndex.value === 0);
const isLast = computed(() => stepIndex.value === steps.value.length - 1);

const locateTarget = (): void => {
    if (!currentStep.value) {
        targetRect.value = null;

        return;
    }

    const el = document.querySelector(`[data-tour="${currentStep.value.target}"]`);

    if (!el) {
        // This step's element isn't on the page right now (e.g. a create-only panel
        // while editing) — skip past it instead of getting stuck. `next()` ends the
        // tour gracefully on its own if this was the last step.
        next();

        return;
    }

    el.scrollIntoView({ block: 'center', behavior: 'smooth' });

    nextTick(() => {
        requestAnimationFrame(() => {
            targetRect.value = el.getBoundingClientRect();
        });
    });
};

const holeStyle = computed(() => {
    if (!targetRect.value) {
        return { display: 'none' };
    }

    const r = targetRect.value;

    return {
        top: `${r.top - PADDING}px`,
        left: `${r.left - PADDING}px`,
        width: `${r.width + PADDING * 2}px`,
        height: `${r.height + PADDING * 2}px`,
    };
});

// Four bars that tile the viewport minus the hole, instead of a single full-screen
// backdrop — so nothing sits on top of the spotlighted element and the user can
// actually click/type into it while the rest of the page stays dimmed and blocked.
const maskBars = computed(() => {
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    if (!targetRect.value) {
        return [{ top: '0px', left: '0px', width: `${vw}px`, height: `${vh}px` }];
    }

    const r = targetRect.value;
    const top = Math.max(0, r.top - PADDING);
    const left = Math.max(0, r.left - PADDING);
    const bottom = Math.min(vh, r.bottom + PADDING);
    const right = Math.min(vw, r.right + PADDING);

    return [
        { top: 0, left: 0, width: vw, height: top },
        { top: bottom, left: 0, width: vw, height: Math.max(0, vh - bottom) },
        { top, left: 0, width: left, height: Math.max(0, bottom - top) },
        { top, left: right, width: Math.max(0, vw - right), height: Math.max(0, bottom - top) },
    ].map((bar) => ({
        top: `${bar.top}px`,
        left: `${bar.left}px`,
        width: `${bar.width}px`,
        height: `${bar.height}px`,
    }));
});

const cardStyle = computed(() => {
    if (!targetRect.value) {
        return { display: 'none' };
    }

    const r = targetRect.value;
    const placement = currentStep.value?.placement ?? 'bottom';
    const gap = PADDING + 12;
    const cardWidth = 320;

    const style: Record<string, string> = {};

    if (placement === 'top') {
        style.left = `${Math.min(Math.max(r.left, 16), window.innerWidth - cardWidth - 16)}px`;
        style.bottom = `${window.innerHeight - r.top + gap}px`;
    } else if (placement === 'left') {
        style.top = `${Math.min(Math.max(r.top, 16), window.innerHeight - 220)}px`;
        style.right = `${window.innerWidth - r.left + gap}px`;
    } else if (placement === 'right') {
        style.top = `${Math.min(Math.max(r.top, 16), window.innerHeight - 220)}px`;
        style.left = `${Math.min(r.right + gap, window.innerWidth - cardWidth - 16)}px`;
    } else {
        style.left = `${Math.min(Math.max(r.left, 16), window.innerWidth - cardWidth - 16)}px`;
        style.top = `${r.bottom + gap}px`;
    }

    return style;
});

const handleResize = (): void => locateTarget();
const handleKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape' && isActive.value) {
        skip();
    }
};

// `flush: 'post'` is required: pages open a modal and call `startTour()` in the same
// tick, and this overlay is mounted high in the tree (MainLayout) so its watcher would
// otherwise run before the page's own watcher/render flips the modal open and patches
// the DOM — causing `locateTarget` to find nothing and skip straight through every step.
watch([isActive, stepIndex], () => {
    if (isActive.value) {
        locateTarget();
    }
}, { flush: 'post' });

onMounted(() => {
    window.addEventListener('resize', handleResize);
    window.addEventListener('scroll', handleResize, true);
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
    window.removeEventListener('scroll', handleResize, true);
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div v-if="isActive && currentStep" class="fixed inset-0 z-100 pointer-events-none">
        <div
            v-for="(bar, i) in maskBars"
            :key="i"
            class="fixed bg-[rgba(15,23,42,0.65)] pointer-events-auto transition-all duration-300"
            :style="bar"
        />

        <div
            class="pointer-events-none absolute rounded-lg ring-2 ring-primary-light transition-all duration-300"
            :style="holeStyle"
        />

        <div
            class="pointer-events-auto fixed z-101 w-80 space-y-3 rounded-xl border border-gray-200 bg-body p-4 shadow-2xl transition-all duration-300 dark:border-gray-700"
            :style="cardStyle"
        >
            <div class="flex items-start justify-between gap-2">
                <h3 class="text-sm font-semibold">{{ currentStep.title }}</h3>
                <button type="button" title="Skip tour" class="shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" @click="skip">
                    <XIcon :size="16" />
                </button>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400">{{ currentStep.body }}</p>

            <div class="flex items-center justify-between pt-1">
                <span class="text-xs text-gray-400">{{ stepIndex + 1 }} of {{ steps.length }}</span>

                <div class="flex items-center gap-2">
                    <button
                        v-if="!isFirst"
                        type="button"
                        class="flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-slate-800"
                        @click="back"
                    >
                        <ChevronLeftIcon :size="14" /> Back
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-1 rounded-md bg-primary-light px-3 py-1.5 text-xs font-medium text-white hover:bg-primary"
                        @click="next"
                    >
                        {{ isLast ? 'Finish' : 'Next' }}
                        <ChevronRightIcon v-if="!isLast" :size="14" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
