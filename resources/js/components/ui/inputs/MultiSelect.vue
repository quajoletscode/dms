<script lang="ts" setup>
      import { CheckIcon, ChevronsUpDownIcon, SearchIcon } from '@lucide/vue';
      import type { HTMLAttributes } from 'vue';
      import { computed, nextTick, onMounted, onUnmounted, ref, useTemplateRef, watch } from 'vue';

      interface SelectOption {
            label: string;
            value: string | number;
      }

      type RawOption = SelectOption | string | number;

      interface Props {
            modelValue?: (string | number)[];
            options: RawOption[];
            placeholder?: string;
            id?: string;
            name?: string;
            label?: string | null;
            size?: 'sm' | 'md' | 'lg';
            disabled?: boolean;
            searchable?: boolean;
            class?: HTMLAttributes['class'];
            labelClass?: HTMLAttributes['class'];
            required?: boolean;
            error?: string;
            /** Shown as the top row; selecting it clears the selection to mean "all/global". */
            selectAllLabel?: string;
      }

      const props = withDefaults(defineProps<Props>(), {
            modelValue: () => [],
            placeholder: '',
            id: () => `ms-${Math.random().toString(36).substring(5)}`,
            name: '',
            label: null,
            size: 'sm',
            searchable: true,
            disabled: false,
            options: () => [],
            class: 'max-auto',
            selectAllLabel: 'Select All',
      });

      const emit = defineEmits<{
            (e: 'update:modelValue', value: (string | number)[]): void;
      }>();

      const containerRef = useTemplateRef('containerRef');
      const dropdownRef = useTemplateRef('dropdownRef');
      const isOpen = ref<boolean>(false);
      const searchTerm = ref<string>('');
      const positionBottom = ref(true);
      const triggerRect = ref<DOMRect | null>(null);
      const searchableInput = useTemplateRef('searchable-input');

      const normalizedOptions = computed<SelectOption[]>(() =>
            props.options.map((opt) => (typeof opt === 'object' && opt !== null ? (opt as SelectOption) : { label: String(opt), value: opt })),
      );

      const filteredOptions = computed(() => {
            if (!searchTerm.value) {
                  return normalizedOptions.value;
            }

            const query = searchTerm.value.toLowerCase();

            return normalizedOptions.value.filter((opt) => opt.label.toLowerCase().includes(query));
      });

      const selectedValues = computed(() => (props.modelValue ?? []).map((v) => String(v)));

      const isAllSelected = computed(() => selectedValues.value.length === 0);

      const isSelected = (value: string | number) => selectedValues.value.includes(String(value));

      const selectedLabel = computed(() => {
            if (isAllSelected.value) {
                  return props.selectAllLabel;
            }

            if (selectedValues.value.length === 1) {
                  const match = normalizedOptions.value.find((opt) => String(opt.value) === selectedValues.value[0]);

                  return match?.label ?? (props.placeholder || 'Select...');
            }

            return `${selectedValues.value.length} selected`;
      });

      const dropdownStyle = computed(() => {
            if (!triggerRect.value) {
                  return {};
            }

            const rect = triggerRect.value;
            const vpHeight = window.innerHeight;

            if (positionBottom.value) {
                  return { top: `${rect.bottom + 4}px`, left: `${rect.left}px`, width: `${rect.width}px` };
            }

            return { bottom: `${vpHeight - rect.top + 4}px`, left: `${rect.left}px`, width: `${rect.width}px` };
      });

      function toggleOption(value: string | number) {
            const current = props.modelValue ?? [];
            const key = String(value);

            if (current.some((v) => String(v) === key)) {
                  emit('update:modelValue', current.filter((v) => String(v) !== key));
            } else {
                  emit('update:modelValue', [...current, value]);
            }
      }

      function selectAll() {
            emit('update:modelValue', []);
      }

      function openList() {
            if (props.disabled) {
                  return;
            }

            document.documentElement.style.overflow = 'hidden';
            document.documentElement.style.pointerEvents = 'none';
            isOpen.value = !isOpen.value;

            nextTick(() => {
                  if (containerRef.value) {
                        const rect = containerRef.value.getBoundingClientRect();
                        triggerRect.value = rect;
                        const vpHeight = window.innerHeight || document.documentElement.clientHeight;
                        const spaceBelow = vpHeight - rect.bottom;
                        const spaceAbove = rect.top;

                        positionBottom.value = spaceBelow >= 220 || spaceBelow > spaceAbove;
                  }
            });
      }

      function closeDropdown() {
            isOpen.value = false;
            searchTerm.value = '';
            document.documentElement.style.overflow = '';
            document.documentElement.style.pointerEvents = '';
      }

      function handleClickOutside(e: MouseEvent) {
            if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
                  closeDropdown();
            }
      }

      const updatePosition = () => {
            if (isOpen.value && containerRef.value) {
                  triggerRect.value = containerRef.value.getBoundingClientRect();
            }
      };

      onMounted(() => {
            window.addEventListener('scroll', updatePosition, true);
            window.addEventListener('resize', updatePosition);
            document.addEventListener('click', handleClickOutside);
      });

      onUnmounted(() => {
            window.removeEventListener('scroll', updatePosition, true);
            window.removeEventListener('resize', updatePosition);
            document.removeEventListener('click', handleClickOutside);
      });

      watch(isOpen, async (open) => {
            if (open && props.searchable) {
                  await nextTick();
                  searchableInput.value?.focus();
            }
      });
</script>

<template>
      <div :class="['input-container relative w-full', props.class]" ref="containerRef">
            <label v-if="props.label" :for="props.id" :class="props.labelClass"
              class="relative mb-1.25 inline-block w-full text-[size:inherit] font-[inherit] capitalize select-none">
                  {{ props.label }}
                  <code v-if="required" class="inline-flex items-center text-sm">⁕</code>
            </label>
            <button type="button" :disabled="props.disabled" :id="props.id" @click="openList" :title="selectedLabel"
              aria-haspopup="listbox" :aria-expanded="isOpen && !props.disabled"
              class="group flex items-center justify-between px-1.25 text-left outline-[1.5px] transition-colors bg-white dark:bg-inherit select-none disabled:cursor-not-allowed disabled:bg-gray-100 dark:disabled:bg-slate-700/20"
              :class="[{ 'outline-primary!': isOpen },
            {
                  'py-2 rounded-md': props.size === 'sm',
                  'py-3 rounded-lg': props.size === 'md',
                  'py-3.5 rounded-lg': props.size === 'lg',
                  'outline-red-500 hover:outline-red-500 focus:outline-primary': props.error
            }
            ]">
                  <span class="truncate">
                        {{ selectedLabel }}
                  </span>
                  <span>
                        <ChevronsUpDownIcon class="text-slate-500 group-focus-within:text-global-light" :size="18" />
                  </span>
            </button>
            <p v-if="error && !isOpen" class="mt-1 animate-in text-sm text-red-500 fade-in slide-in-from-top-1">
                  {{ props.error }}
            </p>
            <Teleport to="body">
                  <Transition enter-active-class="transition duration-100" enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100" leave-active-class="transition duration-100"
                    leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-1">
                        <div v-if="isOpen && !props.disabled" ref="dropdownRef" :style="dropdownStyle"
                          class="fixed z-9999 max-h-80 select-none overflow-hidden rounded-md bg-body text-body shadow-lg outline outline-slate-300 dark:outline-slate-700">
                              <div class="relative mx-auto w-full pointer-events-auto! p-2" v-if="props.searchable">
                                    <SearchIcon class="absolute z-1 top-1/2 left-4 -translate-y-1/2 text-slate-500"
                                      :size="16" />
                                    <input ref="searchable-input" inputmode="search" type="search"
                                      class="relative inline-block w-full rounded-md bg-slate-200 py-2 pl-8 outline-0 placeholder:text-slate-500 dark:bg-slate-800"
                                      placeholder="Search..." @click.stop v-model="searchTerm" />
                              </div>
                              <ul class="relative h-full max-h-52 space-y-1 overflow-y-auto px-2 pointer-events-auto!"
                                role="listbox">
                                    <li @click="selectAll"
                                      class="cursor-pointer truncate rounded px-2 py-1.5 font-semibold transition-colors select-none hover:bg-slate-200 hover:dark:bg-slate-800"
                                      :class="{ 'bg-slate-200 dark:bg-slate-800': isAllSelected }">
                                          <div class="flex items-center justify-between">
                                                <span>{{ props.selectAllLabel }}</span>
                                                <CheckIcon v-if="isAllSelected" :size="18" stroke-width="2.5"
                                                  class="text-global-light" />
                                          </div>
                                    </li>
                                    <li v-if="filteredOptions.length === 0 && searchTerm.length > 0"
                                      class="p-2 text-center truncate">
                                          No results for "{{ searchTerm }}"
                                    </li>
                                    <li v-for="option in filteredOptions" :key="option.value" @click="toggleOption(option.value)"
                                      class="cursor-pointer truncate rounded px-2 py-1.5 transition-colors select-none hover:bg-slate-200 hover:dark:bg-slate-800"
                                      :class="{ 'bg-slate-200 dark:bg-slate-800': isSelected(option.value) }">
                                          <div class="flex items-center justify-between">
                                                <span>{{ option.label }}</span>
                                                <CheckIcon v-if="isSelected(option.value)" :size="18" stroke-width="2.5"
                                                  class="text-global-light" />
                                          </div>
                                    </li>
                              </ul>
                              <div class="border-t border-slate-300 p-2 pointer-events-auto! dark:border-slate-700">
                                    <button type="button" @click="closeDropdown"
                                      class="w-full rounded-md bg-slate-200 py-1.5 text-sm font-semibold dark:bg-slate-800">
                                          Done
                                    </button>
                              </div>
                        </div>
                  </Transition>
            </Teleport>
      </div>
</template>
