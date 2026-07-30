<script lang="ts" setup>
import { usePage } from '@inertiajs/vue3';
import { initials } from '@/composables/useString';

const page = usePage();

const props = defineProps<{
    image?: string;
    name?: string;
}>();
</script>

<template>
    <div
        class="flex h-10! w-10! cursor-pointer items-center justify-center rounded-md bg-primary-light p-4 shadow-lg select-none"
    >
        <img
            v-if="
                page.props.auth.user &&
                page.props.auth.user.avatar &&
                !props.name
            "
            loading="lazy"
            class="border-0"
            :src="page.props.auth.user.avatar"
            :alt="page.props.auth.user.email"
        />
        <h3
            v-else-if="
                page.props.auth.user &&
                !page.props.auth.user.avatar &&
                !props.name
            "
            class="text-xl font-semibold text-white!"
        >
            {{ initials(page.props.auth.user.email) }}
        </h3>
        <h3 v-else class="text-xl font-semibold text-white!">
            {{ initials(props.name) }}
        </h3>
    </div>
</template>

<style scoped></style>
