<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import CheckToggler from '@/components/ui/inputs/CheckToggler.vue';
import NumberInput from '@/components/ui/inputs/NumberInput.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import { index, update } from '@/routes/products';

interface Product {
    id: number;
    sku: string;
    barcode: string | null;
    name: string;
    category_id: number | null;
    unit_id: number;
    cost_price: string;
    wholesale_price: string;
    retail_price: string;
    van_price: string;
    tax_rate: string;
    reorder_level: string;
    track_expiry: boolean;
    is_active: boolean;
}

const props = defineProps<{
    product: Product;
    units: Array<{ id: number; name: string }>;
    categories: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    sku: props.product.sku,
    barcode: props.product.barcode ?? '',
    name: props.product.name,
    category_id: (props.product.category_id ?? '') as string | number,
    unit_id: props.product.unit_id,
    cost_price: props.product.cost_price,
    wholesale_price: props.product.wholesale_price,
    retail_price: props.product.retail_price,
    van_price: props.product.van_price,
    tax_rate: props.product.tax_rate,
    reorder_level: props.product.reorder_level,
    track_expiry: props.product.track_expiry,
    is_active: props.product.is_active,
});

const submit = () => {
    form.put(update(props.product.id).url);
};
</script>

<template>
    <div class="max-w-2xl space-y-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Edit Product</h1>
        </div>

        <form class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <TextInput label="SKU" required v-model="form.sku" :error="form.errors.sku" :disabled="form.processing" />
                <TextInput label="Barcode" v-model="form.barcode" :error="form.errors.barcode" :disabled="form.processing" />
                <TextInput class="sm:col-span-2" label="Name" required v-model="form.name" :error="form.errors.name" :disabled="form.processing" />
                <SelectList
                    label="Unit"
                    required
                    v-model="form.unit_id"
                    :options="props.units.map((u) => ({ label: u.name, value: u.id }))"
                    :error="form.errors.unit_id"
                    :disabled="form.processing"
                />
                <SelectList
                    label="Category"
                    v-model="form.category_id"
                    :options="props.categories.map((c) => ({ label: c.name, value: c.id }))"
                    placeholder="Uncategorized"
                    :error="form.errors.category_id"
                    :disabled="form.processing"
                />
            </div>

            <hr class="border-slate-200 dark:border-slate-700" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <NumberInput label="Cost Price (GHS)" v-model="form.cost_price" :error="form.errors.cost_price" :disabled="form.processing" />
                <NumberInput label="Wholesale Price (GHS)" v-model="form.wholesale_price" :error="form.errors.wholesale_price" :disabled="form.processing" />
                <NumberInput label="Retail Price (GHS)" v-model="form.retail_price" :error="form.errors.retail_price" :disabled="form.processing" />
                <NumberInput label="Van Price (GHS)" v-model="form.van_price" :error="form.errors.van_price" :disabled="form.processing" />
                <NumberInput label="Tax Rate (%)" v-model="form.tax_rate" :error="form.errors.tax_rate" :disabled="form.processing" />
                <NumberInput label="Reorder Level" v-model="form.reorder_level" :error="form.errors.reorder_level" :disabled="form.processing" />
            </div>

            <div class="flex items-center gap-6">
                <CheckToggler v-model="form.track_expiry" label="Track batches / expiry" />
                <CheckToggler v-model="form.is_active" label="Active" />
            </div>

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
