<script lang="ts" setup>

  import { CheckIcon, ChevronDownIcon } from '@lucide/vue';
  import type { HTMLAttributes } from 'vue';
  import {
    computed,
    ref,
    useTemplateRef,
    onUnmounted,
    onMounted,
    nextTick,
  } from 'vue';

  interface SelectOption {
    label: string;
    value: string | number;
  }

  type RawOption = SelectOption | string | number;

  interface Props {
    modelValue?: string | number | null;
    options: RawOption[];
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
    class?: HTMLAttributes['class'];
    size?: 'sm' | 'md' | 'lg';
    label?: string;
    labelClass?: HTMLAttributes['class'];
    id?: string;
    name?: string;
    error?: string;
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: 'Select...',
    disabled: false,
    allowAdd: false,
    required: false,
    options: () => [],
    class: 'max-auto',
    size: 'sm',
    id: () => `ss-${Math.random().toString(16).slice(2)}`,
    name: ''
  });

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | any): void;
  }>();

  const positionBottom = ref(true);
  const isOpen = ref(false);
  const activeIndex = ref(-1);
  const containerRef = useTemplateRef('container');
  const buttonRef = useTemplateRef('button');

  const triggerRect = ref<DOMRect | null>(null);

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

  const normalizedOptions = computed<SelectOption[]>(() => {
    return props.options.map((opt) => {
      if (typeof opt === 'object' && opt !== null) {
        return opt as SelectOption;
      }

      return { label: String(opt), value: opt };
    });
  });

  const selectedLabel = computed(() => {
    // String-normalized, matching the comparison the dropdown list (below) already uses —
    // otherwise a numeric option.value (e.g. 5) never matches a string modelValue (e.g. "5"),
    // and the button silently falls back to the placeholder even though a value IS selected.
    const selected = normalizedOptions.value.find(
      (opt) => opt.value?.toString() === props.modelValue?.toString(),
    );

    return selected ? selected.label : props.placeholder;
  });

  const handleMenuPosition = async () => {
    await nextTick(() => {
      if (containerRef.value) {
        const rect = containerRef.value.getBoundingClientRect();
        triggerRect.value = rect;
        const vpHeight =
          window.innerHeight || document.documentElement.clientHeight;
        const spaceBelow = vpHeight - rect.bottom;
        const spaceAbove = rect.top;

        positionBottom.value = spaceBelow >= 220 || spaceBelow > spaceAbove;
      }
    });
  };

  function toggleList() {
    if (!props.disabled) {
      document.documentElement.classList.toggle('pointer-events-none');
      document.documentElement.dataset.scrollLock = 'true';

      isOpen.value = !isOpen.value;

      handleMenuPosition();

      buttonRef.value?.focus();

      if (isOpen.value) {
        activeIndex.value = normalizedOptions.value.findIndex(
          (opt) => opt.value?.toString() === props.modelValue?.toString(),
        );

        nextTick(() => {
          const activeEl = containerRef.value?.querySelector(
            '.font-semibold',
          ) as HTMLDivElement;

          if (activeEl) {
            activeEl.scrollIntoView({ block: 'center' });
          }
        });
      } else {
        activeIndex.value = -1;
      }
    }
  }

  function selectOption(value: string | number) {
    emit('update:modelValue', value);
    isOpen.value = false;
    activeIndex.value = -1;
    document.documentElement.classList.remove('pointer-events-none');
    document.documentElement.removeAttribute('data-scroll-lock');
  }

  function handleArrowDown(e: KeyboardEvent) {
    e.preventDefault();

    if (!isOpen.value) {
      toggleList();

      return;
    }

    if (activeIndex.value < normalizedOptions.value.length - 1) {
      activeIndex.value++;
    } else {
      activeIndex.value = 0;
    }
  }

  function handleArrowUp(e: KeyboardEvent) {
    e.preventDefault();

    if (!isOpen.value) {
      toggleList();

      return;
    }

    if (activeIndex.value > 0) {
      activeIndex.value--;
    } else {
      activeIndex.value = normalizedOptions.value.length - 1;
    }
  }

  function handleEnter(e: KeyboardEvent) {
    e.preventDefault();

    if (!isOpen.value) {
      toggleList();
    } else if (activeIndex.value >= 0) {
      selectOption(normalizedOptions.value[activeIndex.value].value);
      document.documentElement.classList.remove('pointer-events-none');
      document.documentElement.removeAttribute('data-scroll-lock');
    }
  }

  function handleEscape() {
    isOpen.value = false;
    activeIndex.value = -1;
    buttonRef.value?.focus();
    document.documentElement.classList.remove('pointer-events-none');
    document.documentElement.removeAttribute('data-scroll-lock');
  }

  function handleKeydown(e: KeyboardEvent) {
    switch (e.key) {
      case 'ArrowDown':
        handleArrowDown(e);
        break;
      case 'ArrowUp':
        handleArrowUp(e);
        break;
      case 'Enter':
        handleEnter(e);
        break;
      case 'Escape':
        handleEscape();
        break;
    }
  }

  function handleClickOutside(e: MouseEvent) {
    if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
      isOpen.value = false;
      activeIndex.value = -1;

      document.documentElement.classList.remove('pointer-events-none');
      document.documentElement.removeAttribute('data-scroll-lock');
    }
  }

  const updatePosition = () => {
    if (isOpen.value && containerRef.value) {
      triggerRect.value = containerRef.value.getBoundingClientRect();
    }
  };

  // listen to outside click
  onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('scroll', updatePosition, true);
    window.addEventListener('resize', updatePosition);
  });

  onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('scroll', updatePosition, true);
    window.removeEventListener('resize', updatePosition);
  });
</script>

<template>
  <div ref="container" :class="['input-container relative w-full', props.class]">
    <label v-if="props.label" :for="props.id" :class="props.labelClass"
      class="relative mb-1.25 inline-block w-full font-[inherit] text-[size:inherit] capitalize select-none">
      {{ props.label }}
      <code v-if="required" class="inline-flex items-center text-sm">⁕</code>
    </label>

    <button ref="button" type="button" :id="props.id" :disabled="disabled" @click="toggleList"
      @keydown.down.prevent="handleKeydown" @keydown.up.prevent="handleArrowUp" @keydown.enter.prevent="handleEnter"
      @keydown.esc.prevent="handleEscape" aria-haspopup="listbox" :aria-expanded="isOpen && !props.disabled"
      :aria-controls="isOpen ? `${props.id}-listbox` : undefined"
      class="group px-1.25 text-left outline-[1.5px] bg-white dark:bg-inherit text-nowrap flex items-center justify-between transition-colors select-none disabled:cursor-not-allowed disabled:bg-gray-100 dark:disabled:bg-slate-700/20"
      :class="[{ 'outline-primary!': isOpen }, { 'rounded-md py-2': props.size === 'sm', 'rounded-lg py-3': props.size === 'md', 'rounded-lg py-3.5': props.size === 'lg', }, {
        'outline-red-500 hover:outline-red-500 focus:outline-primary': props.error
      }]" :title="selectedLabel ? selectedLabel : undefined">
      <span class="truncate">
        {{ selectedLabel }}
      </span>
      <span>
        <ChevronDownIcon :size="18" class="text-slate-500" />
      </span>
      <select :required="props.required" :disabled="props.disabled" :value="props.modelValue?.toString()"
        class="pointer-events-none! absolute bottom-0 left-0 size-0 touch-none! appearance-none opacity-0 [-moz-apperance:none] [-webkit-appearance:none]"
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
        enter-to-class="opacity-100" leave-active-class="transition duration-100" leave-from-class="opacity-100"
        leave-to-class="opacity-0 -translate-y-1">
        <ul v-if="isOpen" role="listbox" :style="dropdownStyle"
          class="fixed z-9999 space-y-1 p-2 max-h-80 overflow-y-auto rounded-md bg-body text-body shadow-lg outline outline-slate-300 select-none dark:outline-slate-700">
          <li v-for="(opt, idx) in normalizedOptions" :key="opt.value" @click="selectOption(opt.value)" role="option"
            :aria-selected="props.modelValue?.toString() === opt.value.toString()
              "
            class="pointer-events-auto cursor-pointer truncate rounded px-2 py-1.5 transition-colors hover:bg-slate-200 hover:dark:bg-slate-800"
            :class="{
              'bg-slate-300 font-semibold dark:bg-slate-800':
                props.modelValue?.toString() ===
                opt.value.toString(),
              'bg-slate-200 dark:bg-slate-800': activeIndex === idx,
              'hover:bg-slate-200 hover:dark:bg-slate-800':
                activeIndex !== idx &&
                props.modelValue?.toString() !==
                opt.value.toString(),
            }">
            <div class="flex items-center justify-between">
              <span class="text-wrap">
                {{ opt.label }}
              </span>
              <CheckIcon :stroke-width="2.5" class="text-global-light" v-if="
                props.modelValue?.toString() ===
                opt.value.toString()
              " :size="18" />
            </div>
          </li>
        </ul>
      </Transition>
    </Teleport>
  </div>
</template>
