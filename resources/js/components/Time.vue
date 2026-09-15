<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { formatFullDate } from '@/composables/useDate';

const currentTime = ref(new Date().toLocaleTimeString());
let intervalId: any;
const updateTime = () => {
    const now = new Date();
    currentTime.value = formatFullDate(now);
};
onUnmounted(() => {
    clearInterval(intervalId); // Clear the interval when the component is unmounted
});
onMounted(() => {
    updateTime(); // Initial update
    intervalId = setInterval(updateTime, 1000); // Update every minute
});
</script>

<template>
    {{ currentTime }}
</template>
