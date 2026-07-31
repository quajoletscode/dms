<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import CheckToggler from '@/components/ui/inputs/CheckToggler.vue';
import NumberInput from '@/components/ui/inputs/NumberInput.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import { index, update } from '@/routes/customers';

interface Customer {
    id: number;
    code: string;
    name: string;
    type: string;
    price_category: string | null;
    is_active: boolean;
    credit_limit: string;
}

const props = defineProps<{
    customer: Customer;
}>();

const form = useForm({
    code: props.customer.code,
    name: props.customer.name,
    type: props.customer.type,
    credit_limit: props.customer.credit_limit,
    price_category: props.customer.price_category ?? '',
    is_active: props.customer.is_active,
});

const submit = () => {
    form.put(update(props.customer.id).url);
};
</script>

<template>
    <div class="max-w-xl space-y-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Edit Customer</h1>
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
