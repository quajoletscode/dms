<script lang="ts" setup>
import { useForm } from '@inertiajs/vue3';
import { Loader2Icon, LogInIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import CheckToggler from '@/components/ui/inputs/CheckToggler.vue';
import PasswordInput from '@/components/ui/inputs/PasswordInput.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import { login } from '@/routes';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(login.url(), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">Sign in to DMS</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Distribution Management System</p>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <TextInput
                label="Email"
                type="email"
                required
                autofocus
                v-model="form.email"
                :error="form.errors.email"
                :disabled="form.processing"
            />
            <PasswordInput
                label="Password"
                required
                v-model="form.password"
                :error="form.errors.password"
                :disabled="form.processing"
            />
            <CheckToggler v-model="form.remember" label="Remember me" />

            <Button type="submit" class="flex w-full items-center justify-center gap-2" :disabled="form.processing">
                <Loader2Icon :size="18" class="animate-spin" v-if="form.processing" />
                <LogInIcon :size="18" v-else />
                Sign in
            </Button>
        </form>
    </div>
</template>
