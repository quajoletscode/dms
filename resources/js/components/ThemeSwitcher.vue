<script setup lang="ts">
import { useTheme } from '@/composables/useTheme';

const { theme, toggleTheme } = useTheme();
</script>

<template>
    <label
        for="theme-toggle"
        class="theme-switch relative inline-block h-5.5 w-10 select-none"
        title="Change theme"
    >
        <input
            type="checkbox"
            id="theme-toggle"
            :checked="theme === 'dark'"
            @change="toggleTheme"
        />
        <span class="slider"></span>
    </label>
</template>

<style scoped>
/* 2. Hide the default HTML checkbox */
.theme-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

/* 3. The Slider (the track of the switch) */
.slider {
    position: absolute;
    cursor: pointer;
    padding: 2px 0;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--color-slate-400);
    -webkit-transition: 0.4s;
    transition: 0.4s;
    border-radius: 34px;
    /* Makes it a pill shape */

    &:where(.dark, .dark *) {
        background-color: var(--color-slate-600);
    }
}

/* 4. The Slider Thumb (the circle that moves) */
.slider:before {
    position: absolute;
    content: '';
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 2px;
    background-color: var(--color-gray-200);
    -webkit-transition: 0.4s;
    transition: 0.4s;
    border-radius: 50%;
    /* Makes it a circle */
}

/* 5. The "On" State (when the checkbox is checked) */
input:checked + .slider {
    background-color: var(--color-gray-600);
    /* A blue color for dark mode */
}

input:checked + .slider:before {
    content: '';
    /* Move the thumb to the right */
    -webkit-transform: translateX(18px);
    -ms-transform: translateX(18px);
    transform: translateX(18px);
    background-color: var(--color-gray-900);
}
</style>
