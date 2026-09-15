<script lang="ts" setup>
import { useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveAll, ShieldIcon } from '@lucide/vue';
import Avatar from '@/components/Avatar.vue';
import Button from '@/components/ui/Button.vue';
import PasswordInput from '@/components/ui/inputs/PasswordInput.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import { update } from '@/routes/profile';

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    username: string;
    email: string;
    avatar: string | null;
    role: Role | null;
}

const props = defineProps<{
    user: User;
    breadcrumb: Array<{ label: string; url: string }>;
}>();

const form = useForm({
    name: props.user.name,
    username: props.user.username,
    email: props.user.email,
    current_password: '',
    password: '',
    password_confirmation: '',
});

const handleSubmit = () => {
    form.put(update().url, {
        showProgress: false,
        onSuccess: () => {
            form.reset('current_password', 'password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <div class="space-y-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                My Profile
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Manage your personal information and password.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Avatar & Role Card -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex flex-col items-center gap-4 text-center">
                    <Avatar
                        :src="user.avatar"
                        :alt="user.name"
                        :name="user.name"
                        size="lg"
                    />
                    <div>
                        <p
                            class="text-lg font-semibold text-slate-900 dark:text-slate-100"
                        >
                            {{ user.name }}
                        </p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            @{{ user.username }}
                        </p>
                    </div>
                    <div
                        class="bg-primary-light/10 text-primary-light flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium"
                    >
                        <ShieldIcon :size="14" />
                        <span class="capitalize">{{
                            user.role?.name?.replace('_', ' ') ?? 'No Role'
                        }}</span>
                    </div>
                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Role is managed by an administrator and cannot be
                        changed here.
                    </p>
                </div>
            </div>

            <!-- Profile Form -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2 dark:border-slate-700 dark:bg-slate-800"
            >
                <form class="space-y-6" @submit.prevent="handleSubmit">
                    <!-- Personal Info -->
                    <div>
                        <h2
                            class="mb-4 text-base font-semibold text-slate-700 dark:text-slate-300"
                        >
                            Personal Information
                        </h2>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextInput
                                label="Full Name"
                                placeholder="e.g., John Doe"
                                required
                                v-model="form.name"
                                :error="form.errors.name"
                                :disabled="form.processing"
                            />
                            <TextInput
                                label="Username"
                                placeholder="e.g., johndoe"
                                required
                                v-model="form.username"
                                :error="form.errors.username"
                                :disabled="form.processing"
                            />
                            <TextInput
                                class="sm:col-span-2"
                                label="Email Address"
                                placeholder="e.g., john@example.com"
                                type="email"
                                required
                                v-model="form.email"
                                :error="form.errors.email"
                                :disabled="form.processing"
                            />
                        </div>
                    </div>

                    <hr class="border-slate-200 dark:border-slate-700" />

                    <!-- Change Password -->
                    <div>
                        <h2
                            class="mb-1 text-base font-semibold text-slate-700 dark:text-slate-300"
                        >
                            Change Password
                        </h2>
                        <p
                            class="mb-4 text-xs text-slate-400 dark:text-slate-500"
                        >
                            Leave the fields blank if you don't want to change
                            your password.
                        </p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <PasswordInput
                                class="sm:col-span-2"
                                label="Current Password"
                                placeholder="Enter current password"
                                v-model="form.current_password"
                                :error="form.errors.current_password"
                                :disabled="form.processing"
                            />
                            <PasswordInput
                                label="New Password"
                                placeholder="Enter new password"
                                v-model="form.password"
                                :error="form.errors.password"
                                :disabled="form.processing"
                                :required="form.current_password !== ''"
                            />
                            <PasswordInput
                                label="Confirm New Password"
                                placeholder="Confirm new password"
                                v-model="form.password_confirmation"
                                :error="form.errors.password_confirmation"
                                :disabled="form.processing"
                                :required="form.password !== ''"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="flex items-center gap-2"
                        >
                            <Loader2Icon
                                :size="18"
                                class="animate-spin"
                                v-if="form.processing"
                            />
                            <SaveAll :size="18" v-else />
                            Save Changes
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
