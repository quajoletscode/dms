<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import CheckToggler from '@/components/ui/inputs/CheckToggler.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import { index, update } from '@/routes/suppliers';

interface Supplier {
    id: number;
    code: string;
    name: string;
    contact: string | null;
    payment_terms: string | null;
    is_active: boolean;
}

const props = defineProps<{
    supplier: Supplier;
}>();

const form = useForm({
    code: props.supplier.code,
    name: props.supplier.name,
    contact: props.supplier.contact ?? '',
    payment_terms: props.supplier.payment_terms ?? '',
    is_active: props.supplier.is_active,
});

const submit = () => {
    form.put(update(props.supplier.id).url);
};
</script>

<template>
    <div class="max-w-xl space-y-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Edit Supplier</h1>
        </div>

        <form class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800" @submit.prevent="submit">
            <TextInput label="Code" required v-model="form.code" :error="form.errors.code" :disabled="form.processing" />
            <TextInput label="Name" required v-model="form.name" :error="form.errors.name" :disabled="form.processing" />
            <TextInput label="Contact" v-model="form.contact" :error="form.errors.contact" :disabled="form.processing" />
            <TextInput label="Payment Terms" v-model="form.payment_terms" :error="form.errors.payment_terms" :disabled="form.processing" />
            <CheckToggler v-model="form.is_active" label="Active" />

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
