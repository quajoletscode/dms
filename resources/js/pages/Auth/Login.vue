<script lang="ts" setup>
import { useForm } from '@inertiajs/vue3';
import { KeyRoundIcon, Loader2Icon, LogInIcon } from '@lucide/vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import CheckToggler from '@/components/ui/inputs/CheckToggler.vue';
import PasswordInput from '@/components/ui/inputs/PasswordInput.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import { login } from '@/routes';

interface DemoAccount {
    email: string;
    label: string;
    password: string;
}

const props = defineProps<{
    demoAccounts: DemoAccount[];
}>();

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

const loginAs = (account: DemoAccount) => {
    form.email = account.email;
    form.password = account.password;
    submit();
};
</script>

<template>
    <div class="w-full max-w-sm space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-700 dark:bg-slate-800">
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

        <div
            v-if="props.demoAccounts.length"
            class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-900/40"
        >
            <div class="flex items-center gap-2">
                <KeyRoundIcon :size="14" class="text-slate-400" />
                <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase dark:text-slate-400">Demo accounts (local only)</p>
            </div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">One click signs you in as that role so you can see exactly what it can access.</p>

            <ul class="mt-3 space-y-1.5">
                <li v-for="account in props.demoAccounts" :key="account.email">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2 text-left transition-colors hover:border-primary-light disabled:pointer-events-none disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800"
                        :disabled="form.processing"
                        @click="loginAs(account)"
                    >
                        <span class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ account.label }}</span>
                        <Badge variant="neutral">{{ account.email }}</Badge>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
