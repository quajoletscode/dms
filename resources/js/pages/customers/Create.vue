<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import NumberInput from '@/components/ui/inputs/NumberInput.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import { index, store } from '@/routes/customers';

const form = useForm({
    code: '',
    name: '',
    type: 'wholesale' as string,
    credit_limit: '',
    price_category: '',
});

const submit = () => {
    form.post(store().url);
};
</script>

<template>
    <div class="max-w-xl space-y-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">New Customer</h1>
        </div>

        <form class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800" @submit.prevent="submit">
            <TextInput label="Code" required v-model="form.code" :error="form.errors.code" :disabled="form.processing" />
            <TextInput label="Name" required v-model="form.name" :error="form.errors.name" :disabled="form.processing" />
            <SelectList
                label="Type"
                required
                v-model="form.type"
                :options="[
                    { label: 'Wholesale', value: 'wholesale' },
                    { label: 'Retailer', value: 'retailer' },
                ]"
                :error="form.errors.type"
                :disabled="form.processing"
            />
            <NumberInput label="Credit Limit (GHS)" v-model="form.credit_limit" :error="form.errors.credit_limit" :disabled="form.processing" />
            <TextInput label="Price Category" v-model="form.price_category" :error="form.errors.price_category" :disabled="form.processing" />

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
