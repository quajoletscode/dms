<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import NumberInput from '@/components/ui/inputs/NumberInput.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import { index, store } from '@/routes/suppliers';

const form = useForm({
    code: '',
    name: '',
    contact: '',
    payment_terms: '',
    opening_balance: '',
});

const submit = () => {
    form.post(store().url);
};
</script>

<template>
    <div class="max-w-xl space-y-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">New Supplier</h1>
        </div>

        <form class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800" @submit.prevent="submit">
            <TextInput label="Code" required v-model="form.code" :error="form.errors.code" :disabled="form.processing" />
            <TextInput label="Name" required v-model="form.name" :error="form.errors.name" :disabled="form.processing" />
            <TextInput label="Contact" v-model="form.contact" :error="form.errors.contact" :disabled="form.processing" />
            <TextInput label="Payment Terms" v-model="form.payment_terms" :error="form.errors.payment_terms" :disabled="form.processing" />
            <NumberInput label="Opening Balance (GHS)" v-model="form.opening_balance" :error="form.errors.opening_balance" :disabled="form.processing" />

            <div class="flex justify-end gap-3">
                <Link :href="index().url" class="button ghost">Cancel</Link>
                <Button type="submit" class="flex items-center gap-2" :disabled="form.processing">
                    <Loader2Icon :size="18" class="animate-spin" v-if="form.processing" />
                    <SaveIcon :size="18" v-else />
                    Save
                </Button>
            </div>
        </form>
    </div>
</template>
