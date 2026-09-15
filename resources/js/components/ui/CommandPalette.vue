<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowRightIcon, SearchIcon, XIcon } from '@lucide/vue';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import type { NavigationCommand } from '@/composables/useNavigationCommands';

const props = defineProps<{
    open: boolean;
    commands: NavigationCommand[];
    quickActions: NavigationCommand[];
}>();

const emit = defineEmits<{
    (event: 'close'): void;
}>();

const searchInput = useTemplateRef<HTMLInputElement>('search-input');
const query = ref('');
const activeIndex = ref(0);

const normalize = (value: string): string => value.trim().toLowerCase();

const scoreCommand = (command: NavigationCommand, term: string): number => {
    const name = normalize(command.name);
    const group = normalize(command.group);
    const description = normalize(command.description);

    if (name === term) {
        return 0;
    }

    if (name.startsWith(term)) {
        return 1;
    }

    if (name.includes(term)) {
        return 2;
    }

    if (group.includes(term)) {
        return 3;
    }

    if (description.includes(term)) {
        return 4;
    }

    return Number.MAX_SAFE_INTEGER;
};

const filteredCommands = computed(() => {
    const term = normalize(query.value);

    if (!term) {
        return props.quickActions;
    }

    return props.commands
        .map((command) => ({
            command,
            score: scoreCommand(command, term),
        }))
        .filter((result) => result.score < Number.MAX_SAFE_INTEGER)
        .sort((first, second) => {
            if (first.score !== second.score) {
                return first.score - second.score;
            }

            return first.command.name.localeCompare(second.command.name);
        })
        .map((result) => result.command)
        .slice(0, 10);
});

const close = () => {
    emit('close');
};

const runCommand = (command: NavigationCommand | undefined) => {
    if (!command) {
        return;
    }

    router.visit(command.to);
    query.value = '';
    activeIndex.value = 0;
    close();
};

const moveActive = (offset: number) => {
    if (!filteredCommands.value.length) {
        return;
    }

    const nextIndex =
        (activeIndex.value + offset + filteredCommands.value.length) %
        filteredCommands.value.length;

    activeIndex.value = nextIndex;
};

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        close();
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        moveActive(1);
        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        moveActive(-1);
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        runCommand(filteredCommands.value[activeIndex.value]);
    }
};

watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            await nextTick();
            searchInput.value?.focus();
            return;
        }

        query.value = '';
        activeIndex.value = 0;
    },
);

watch(filteredCommands, () => {
    activeIndex.value = 0;
});
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="props.open"
                class="fixed inset-0 z-150 bg-slate-950/45 px-3 py-4 backdrop-blur-sm print:hidden sm:px-6"
                @click.self="close"
            >
                <div
                    class="mx-auto flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-lg border border-white/30 bg-white shadow-2xl ring-1 ring-slate-950/10 dark:border-slate-700 dark:bg-slate-900 dark:ring-white/10"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="command-palette-title"
                    @keydown="handleKeydown"
                >
                    <div
                        class="flex items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950"
                    >
                        <SearchIcon
                            :size="18"
                            class="shrink-0 text-cyan-600 dark:text-cyan-300"
                        />
                        <label id="command-palette-title" class="sr-only">
                            Command center
                        </label>
                        <input
                            ref="search-input"
                            v-model="query"
                            type="search"
                            autocomplete="off"
                            spellcheck="false"
                            class="h-10 min-w-0 flex-1 bg-transparent text-base text-slate-900 outline-0 placeholder:text-slate-400 dark:text-slate-100 dark:placeholder:text-slate-500"
                            placeholder="Search pages or actions"
                        />
                        <button
                            type="button"
                            class="grid h-9 w-9 shrink-0 place-items-center rounded-md text-slate-500 transition-colors hover:bg-slate-200 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-cyan-500 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                            title="Close"
                            @click="close"
                        >
                            <XIcon :size="18" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto p-2 smart-scroll">
                        <div
                            v-if="filteredCommands.length"
                            class="flex flex-col gap-1"
                        >
                            <button
                                v-for="(command, index) in filteredCommands"
                                :key="command.id"
                                type="button"
                                class="flex min-h-14 w-full items-center gap-3 rounded-md px-3 py-2 text-left transition-colors focus-visible:outline-2 focus-visible:outline-cyan-500"
                                :class="
                                    index === activeIndex
                                        ? 'bg-cyan-50 text-slate-950 dark:bg-cyan-400/10 dark:text-white'
                                        : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
                                "
                                @mouseenter="activeIndex = index"
                                @click="runCommand(command)"
                            >
                                <span
                                    class="grid h-9 w-9 shrink-0 place-items-center rounded-md border border-slate-200 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300"
                                >
                                    <component
                                        :is="command.icon"
                                        v-if="command.icon"
                                        :size="18"
                                    />
                                    <SearchIcon v-else :size="18" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate text-sm font-semibold capitalize"
                                    >
                                        {{ command.name }}
                                    </span>
                                    <span
                                        class="block truncate text-xs capitalize text-slate-500 dark:text-slate-400"
                                    >
                                        {{ command.description }}
                                    </span>
                                </span>
                                <ArrowRightIcon
                                    :size="16"
                                    class="shrink-0 text-slate-400"
                                />
                            </button>
                        </div>
                        <div
                            v-else
                            class="grid min-h-32 place-items-center rounded-md border border-dashed border-slate-300 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
                        >
                            No matching commands.
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
