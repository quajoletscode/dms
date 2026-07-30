<script lang="ts" setup>
      import { CheckIcon, ChevronsUpDownIcon, DotIcon, SearchIcon } from '@lucide/vue';
      import type { HTMLAttributes } from 'vue';
      import { computed, nextTick, onMounted, onUnmounted, ref, useTemplateRef, watch } from 'vue';

      interface SelectOption {
            label: string
            value: string | number | Record<string, any>
      }

      type RawOption = SelectOption | string | number


      interface Props {
            modelValue?: string | number | Record<string, any> | null;
            options: RawOption[];
            placeholder?: string;
            id?: string;
            name?: string;
            label?: string | null,
            size?: 'sm' | 'md' | 'lg';
            disabled?: boolean;
            searchable?: boolean;
            allowAdd?: boolean;
            class?: HTMLAttributes['class'],
            style?: HTMLAttributes['style'],
            labelClass?: HTMLAttributes['class'],
            tabIndex?: string,
            required?: boolean,
            error?: string;
      }

      const props = withDefaults(defineProps<Props>(), {
            modelValue: '',
            placeholder: '',
            id: () => `sl-${Math.random().toString(36).substring(5)}`,
            name: '',
            label: null,
            size: 'sm',
            searchable: true,
            disabled: false,
            allowAdd: false,
            options: () => [],
            class: 'max-auto'
      });

      const emit = defineEmits<{
            (e: 'update:modelValue', value: string | number | Record<string, any>): void
      }>();

      const containerRef = useTemplateRef('containerRef');
      const dropdownRef = useTemplateRef('dropdownRef');
      const activeIndex = ref(-1);
      const isOpen = ref<boolean>(false);
      const searchTerm = ref<string>('');
      const positionBottom = ref(true);
      const selectInput = useTemplateRef('select-input');

      const triggerRect = ref<DOMRect | null>(null);

      const dropdownStyle = computed(() => {
            if (!triggerRect.value) return {};
            const rect = triggerRect.value;
            const vpHeight = window.innerHeight;
            if (positionBottom.value) {
                  return { top: `${rect.bottom + 4}px`, left: `${rect.left}px`, width: `${rect.width}px` };
            }
            return { bottom: `${vpHeight - rect.top + 4}px`, left: `${rect.left}px`, width: `${rect.width}px` };
      });

      function openList() {

            if (!props.disabled) {

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

                  if (isOpen.value) {
                        activeIndex.value = normalizedOptions.value.findIndex(
                              (opt) => opt.value.toString() === props.modelValue?.toString()
                        );

                        nextTick(() => {
                              const activeEl = dropdownRef.value?.querySelector('.selected') as HTMLDivElement;

                              if (activeEl) {

                                    activeEl.scrollIntoView({ block: 'center' });
                              }
                        });

                  } else {
                        activeIndex.value = -1;
                  }
            }

      }

      function closeDropdown() {
            isOpen.value = false;
            activeIndex.value = -1;
            searchTerm.value = '';
            document.documentElement.style.overflow = '';
            document.documentElement.style.pointerEvents = '';
      }

      function selectOption(option: SelectOption | { label: string, value: any }) {
            if (selectInput.value) {
                  selectInput.value.value = option.value;
            }

            emit('update:modelValue', option.value);
            closeDropdown();
      }

      const normalizedOptions = computed<SelectOption[]>(() => {
            return props.options.map((opt) => {
                  if (typeof opt === 'object' && opt !== null) {
                        return opt as SelectOption
                  }

                  // Handle primitive types (string/number)
                  return { label: String(opt), value: opt }
            })
      })

      const filteredOptions = computed(() => {
            if (!searchTerm.value) {
                  return normalizedOptions.value
            }

            const query = searchTerm.value.toLowerCase()

            return normalizedOptions.value.filter((opt) =>
                  opt.label.toLowerCase().includes(query)
            )
      });

      // Show custom value option if allowAdd is enabled and search term doesn't match any option
      const displayOptions = computed(() => {
            const options = filteredOptions.value;

            if (props.allowAdd && searchTerm.value.trim() !== '' &&
                  !options.some(opt => opt.label.toLowerCase() === searchTerm.value.toLowerCase())) {
                  return [
                        ...options,
                        { label: `Add "${searchTerm.value}"`, value: searchTerm.value }
                  ];
            }

            return options;
      });

      watch(filteredOptions, () => {
            // Reset active index when filter changes
            activeIndex.value = -1;
      });

      const selectedLabel = computed<string>(() => {
            const currentVal = props.modelValue?.toString();

            const selected = normalizedOptions.value.find(
                  (opt) => String(opt.value) === currentVal
            );

            return selected ? selected.label : (props.placeholder || 'Select any');
      });

      // Keyboard Navigation Logic
      function onArrowDown() {
            if (!isOpen.value) {
                  openList();

                  return;
            }

            if (displayOptions.value.length === 0) {
                  return;
            }

            if (activeIndex.value < displayOptions.value.length - 1) {
                  activeIndex.value++;
            } else {
                  activeIndex.value = 0;
            }
      }

      function onArrowUp() {
            if (!isOpen.value) {
                  openList();

                  return;
            }

            if (displayOptions.value.length === 0) {
                  return;
            }

            if (activeIndex.value > 0) {
                  activeIndex.value--;
            } else {
                  activeIndex.value = displayOptions.value.length - 1;
            }
      }

      function onEnter() {
            if (activeIndex.value >= 0 && displayOptions.value[activeIndex.value]) {
                  selectOption(displayOptions.value[activeIndex.value]);
            } else if (props.allowAdd && searchTerm.value.trim() !== '') {
                  selectOption({ label: searchTerm.value, value: searchTerm.value });
            }

      }


      function onSearchKeydown(event: KeyboardEvent) {
            switch (event.key) {
                  case 'ArrowDown':
                        event.preventDefault();
                        onArrowDown();
                        break;
                  case 'ArrowUp':
                        event.preventDefault();
                        onArrowUp();
                        break;
                  case 'Enter':
                        event.preventDefault();
                        onEnter();
                        break;
                  case 'Escape':
                        event.preventDefault();
                        closeDropdown();
                        break;
            }
      }

      const searchable_input = useTemplateRef('searchable-input');

      const updatePosition = () => {
            if (isOpen.value && containerRef.value) {
                  triggerRect.value = containerRef.value.getBoundingClientRect();
            }
      };

      function handleClickOutside(e: MouseEvent) {
            if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
                  closeDropdown();
            }
      }

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

      watch(() => isOpen.value, async (open) => {
            if (open && props.searchable) {
                  await nextTick();
                  searchable_input.value?.focus();
            }
      });
</script>

<template>
      <div :class="['relative w-full input-container', props.class, props.style]" ref="containerRef">
            <label v-if="props.label" :for="props.id" :class="props.labelClass"
              class="inline-block relative w-full text-[size:inherit] font-[inherit] capitalize mb-1.25 select-none">
                  {{ props.label }}
                  <code v-if="required" class="inline-flex items-center text-sm">⁕</code>
            </label>
            <button type="button" :tabindex="props.tabIndex" :disabled="props.disabled" :id="props.id" @click="openList"
              @keydown.down.prevent="onArrowDown" @keydown.up.prevent="onArrowUp" @keydown.enter.prevent="onEnter"
              @keydown.esc.prevent="closeDropdown" :title="selectedLabel" aria-haspopup="listbox"
              :aria-expanded="isOpen && !props.disabled"
              class="group px-1.25 text-left outline-[1.5px] bg-white dark:bg-inherit flex items-center justify-between transition-colors select-none disabled:cursor-not-allowed disabled:bg-gray-100 dark:disabled:bg-slate-700/20"
              :class="[{ 'outline-primary!': isOpen },
            {
                  'py-2 rounded-md': props.size === 'sm',
                  'py-3 rounded-lg': props.size === 'md',
                  'py-3.5 rounded-lg': props.size === 'lg',
                  'outline-red-500 hover:outline-red-500 focus:outline-primary': props.error
            }
            ]">
                  <span class="truncate" :class="[
                        selectedLabel !== '' ? 'text-body' : 'dark:text-slate-500'
                  ]">
                        {{ selectedLabel }}
                  </span>
                  <span>
                        <ChevronsUpDownIcon class="text-slate-500 group-focus-within:text-global-light" :size="18" />
                  </span>

                  <select :required="props.required" :disabled="props.disabled" :value="props.modelValue?.toString()"
                    class="absolute size-0 opacity-0 bottom-0 left-0 appearance-none! [-moz-apperance:none]! [-webkit-appearance:none]! pointer-events-none! touch-none!"
                    ref="select-input" tabindex="-1" :name="props.name || props.id" :id="props.id">
                        <option :value="props.modelValue?.toString()" selected>
                              {{ selectedLabel }}
                        </option>
                  </select>
            </button>
            <p v-if="error && !isOpen" class="mt-1 animate-in text-sm text-red-500 fade-in slide-in-from-top-1">
                  {{ props.error }}
            </p>
            <Teleport to="body">
                  <Transition enter-active-class="transition duration-100" enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100" leave-active-class="transition duration-100"
                    leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-1">
                        <div v-if="isOpen && !props.disabled" ref="dropdownRef" :style="dropdownStyle"
                          class="fixed z-9999 pb-2 select-none max-h-80 overflow-hidden rounded-md shadow-lg bg-body text-body outline outline-slate-300 dark:outline-slate-700">
                              <div class="relative mx-auto w-full pointer-events-auto! p-2" v-if="props.searchable">
                                    <SearchIcon class="absolute z-1 left-4 top-1/2 -translate-y-1/2 text-slate-500"
                                      :size="16" />
                                    <input ref="searchable-input" @keydown="onSearchKeydown" tabindex="1"
                                      inputmode="search" type="search" name="" id=""
                                      class="dark:bg-slate-800 bg-slate-200 outline-0 relative pl-8 placeholder:text-slate-500 py-2 rounded-md inline-block w-full"
                                      placeholder="Search..." @click.stop v-model="searchTerm" />
                              </div>
                              <ul class="h-full px-2 overflow-y-auto max-h-45 relative pointer-events-auto! space-y-1"
                                ref="dropdownRef" role="listbox">
                                    <li v-if="displayOptions.length === 0 && searchTerm.length > 0"
                                      class="p-2 text-center truncate">
                                          No results for "{{ searchTerm }}"
                                    </li>
                                    <li :tabindex="i + 2" v-for="(option, i) in displayOptions" :key="i"
                                      @click="selectOption(option)"
                                      class="relative cursor-pointer select-none transition-colors hover:bg-slate-200 hover:dark:bg-slate-800 py-1.5 px-2 rounded truncate focus-within:outline-0 focus-within:dark:bg-slate-800 focus-within:bg-slate-200"
                                      :class="{ 'selected bg-slate-200 dark:bg-slate-800 font-semibold': activeIndex === i }">
                                          <div class="flex items-center justify-between">
                                                <span>
                                                      {{ option.label }}
                                                </span>
                                                <CheckIcon :size="18" stroke-width="2.5" class="text-global-light"
                                                  v-if="props.modelValue?.toString() === option.value.toString()" />
                                          </div>
                                    </li>
                              </ul>
                        </div>
                  </Transition>
            </Teleport>
      </div>
</template>