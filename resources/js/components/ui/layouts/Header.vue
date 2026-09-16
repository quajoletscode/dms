<script lang="ts" setup>
import { router, usePage } from '@inertiajs/vue3';
import {
    AlignLeftIcon,
    GaugeIcon,
    SearchIcon,
    SparklesIcon,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import type { HTMLAttributes } from 'vue';
import Time from '@/components/Time.vue';
import CommandPalette from '@/components/ui/CommandPalette.vue';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import { useNavigationCommands } from '@/composables/useNavigationCommands';

const props = defineProps<{
    class?: HTMLAttributes['class'];
    library?: boolean;
    acccounts?: boolean;
    superAdmin?: boolean;
}>();

const emits = defineEmits<{
    (event: 'toggleMenu'): void;
}>();

const page = usePage();
const isCommandPaletteOpen = ref(false);
const { commands, quickActions, navigationGroups } = useNavigationCommands();

const visibleQuickActions = computed(() => quickActions.value.slice(0, 3));
const navSections = computed(() =>
    navigationGroups.value.map((group) => ({
        ...group,
        title: group.group
            .split('-')
            .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
            .join(' '),
        items: group.items.map((item) => ({
            ...item,
            displayName: item.name
                .split(' ')
                .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
                .join(' '),
        })),
    })),
);
const roleLabel = computed(() => page.props.auth.role?.replaceAll('_', ' ') ?? 'operator');
const userLabel = computed(
    () => page.props.auth.user?.name ?? page.props.auth.user?.email ?? 'DMS',
);

const openCommandPalette = () => {
    isCommandPaletteOpen.value = true;
};

const closeCommandPalette = () => {
    isCommandPaletteOpen.value = false;
};

const visit = (url: string) => {
    router.visit(url);
};

const handleGlobalKeydown = (event: KeyboardEvent) => {
    const key = event.key.toLowerCase();
    const isPrimaryShortcut = (event.ctrlKey || event.metaKey) && key === 'k';
    const isOfficeStyleShortcut = event.altKey && key === 'q';

    if (!isPrimaryShortcut && !isOfficeStyleShortcut) {
        return;
    }

    event.preventDefault();
    openCommandPalette();
};

onMounted(() => {
    document.addEventListener('keydown', handleGlobalKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleGlobalKeydown);
});
</script>

<template>
    <header
        class="sticky top-0 z-40 w-full print:hidden"
        :class="props.class"
    >
        <div class="flex h-16 items-center justify-between bg-gradient-to-r from-[#0e5e7f] via-[#0b4d6d] to-[#0a87a1] px-5 text-white shadow-sm">
            <div class="flex items-center gap-3">
                <button
                    class="grid h-10 w-10 shrink-0 place-items-center rounded-md text-white/90 transition-colors hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-cyan-200 lg:hidden"
                    @click="emits('toggleMenu')"
                    type="button"
                    title="Open navigation"
                >
                    <AlignLeftIcon :size="22" />
                </button>

                <div class="flex items-center gap-3 text-white">
                    <div class="flex h-7 w-7 items-center justify-center rounded-sm bg-white/10 ring-1 ring-white/20">
                        <span class="flex gap-1">
                            <span class="block h-3.5 w-1 rounded-sm bg-white/90" />
                            <span class="block h-3.5 w-1 rounded-sm bg-white/75" />
                            <span class="block h-3.5 w-1 rounded-sm bg-white/60" />
                        </span>
                    </div>
                    <span class="text-[15px] font-semibold tracking-tight">Dynamics 365 Business Central</span>
                </div>
            </div>

            <div class="flex items-center gap-3 text-white/85">
                <button class="flex h-8 w-8 items-center justify-center rounded-md bg-white/5 hover:bg-white/10" type="button" aria-label="Search">
                    <SearchIcon :size="16" />
                </button>
                <button class="flex h-8 w-8 items-center justify-center rounded-md bg-white/5 hover:bg-white/10" type="button" aria-label="Alerts">
                    <span class="text-sm">◌</span>
                </button>
                <button class="flex h-8 w-8 items-center justify-center rounded-md bg-white/5 hover:bg-white/10" type="button" aria-label="Settings">
                    <span class="text-sm">⚙</span>
                </button>
                <button class="flex h-8 w-8 items-center justify-center rounded-md bg-white/5 hover:bg-white/10" type="button" aria-label="Profile">
                    <span class="text-sm">◉</span>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between border-b border-slate-200 bg-[#f4f6f9] px-5 py-3 shadow-sm">
            <div class="flex min-w-0 flex-1 items-center gap-5 text-[15px] font-medium text-slate-700">
                <span class="font-semibold text-slate-900">CRONUS USA, Inc.</span>
                <div class="hidden min-w-0 flex-wrap items-center gap-4 md:flex">
                    <div v-for="section in navSections" :key="section.group" class="group relative">
                        <button
                            type="button"
                            class="flex items-center gap-1 whitespace-nowrap rounded-sm px-1 py-1.5 transition hover:text-slate-900"
                            :class="page.url.includes(section.items[0]?.to ?? '') ? 'text-slate-900 underline decoration-[#14afb9] decoration-2 underline-offset-8' : ''"
                        >
                            <span>{{ section.title }}</span>
                            <span v-if="section.items.length" class="text-[10px] text-slate-400">▾</span>
                        </button>

                        <div
                            v-if="section.items.length"
                            class="absolute left-0 top-full z-50 hidden pt-2 group-hover:block"
                        >
                            <!--
                                The pt-2 above (not mt-2 on this div) keeps the
                                gap between the button and the panel inside
                                this element's own hoverable box. A margin
                                here would leave that gap empty, so crossing
                                it drops :hover for a frame and the panel
                                closes before the pointer ever reaches it.
                            -->
                            <div class="min-w-48 rounded-md border border-slate-200 bg-white p-1.5 shadow-lg">
                                <button
                                    v-for="item in section.items"
                                    :key="item.name"
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-sm px-2 py-1.5 text-left text-sm text-slate-700 transition hover:bg-slate-100 hover:text-slate-900"
                                    @click="visit(item.to)"
                                >
                                    <span>{{ item.displayName }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden items-center gap-2 xl:flex">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#d8f7f9] text-[11px] font-bold text-[#0d7a80]">EA</div>
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#dfeefb] text-[11px] font-bold text-[#2b5cc9]">PA</div>
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#ddf9ef] text-[11px] font-bold text-[#1a8164]">SO</div>
            </div>
        </div>

        <CommandPalette
            :open="isCommandPaletteOpen"
            :commands="commands"
            :quick-actions="quickActions"
            @close="closeCommandPalette"
        />
    </header>
</template>
