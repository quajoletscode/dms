<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import { computed } from 'vue';
import ProductLineItemsGrid, { type LineItemColumn, type LineItemRow, type ProductOption } from '@/components/ProductLineItemsGrid.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import DateInput from '@/components/ui/inputs/DateInput.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import { index, store } from '@/routes/purchase-orders';

const props = defineProps<{
    suppliers: Array<{ id: number; name: string }>;
    warehouses: Array<{ id: number; name: string }>;
    products: Array<{ id: number; sku: string; name: string; cost_price: string }>;
}>();

const form = useForm<{
    supplier_id: string | number;
    warehouse_id: string | number;
    expected_delivery_date: string;
    items: LineItemRow[];
}>({
    supplier_id: '',
    warehouse_id: '',
    expected_delivery_date: '',
    items: [],
});

const productOptions = computed<ProductOption[]>(() =>
    props.products.map((product) => ({
        id: product.id,
        label: `${product.sku} — ${product.name}`,
        defaults: { unit_cost: product.cost_price },
    })),
);

const columns: LineItemColumn[] = [
    { key: 'qty_ordered', label: 'Qty' },
    { key: 'unit_cost', label: 'Unit Cost (GHS)' },
    { key: 'discount', label: 'Discount' },
    { key: 'tax', label: 'Tax' },
];

const filledItems = computed(() => form.items.filter((row) => row.product_id !== '' && row.qty_ordered !== ''));

const estimatedTotal = computed(() =>
    filledItems.value
        .reduce((sum, row) => {
            const qty = Number(row.qty_ordered) || 0;
            const unitCost = Number(row.unit_cost) || 0;
            const discount = Number(row.discount) || 0;
            const tax = Number(row.tax) || 0;

            return sum + qty * unitCost - discount + tax;
        }, 0)
        .toFixed(2),
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        items: filledItems.value,
    })).post(store().url);
};
</script>

<template>
    <div class="max-w-4xl space-y-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">New Purchase Order</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Order products from a supplier into a warehouse.</p>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <Card>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <SelectList
                        label="Supplier"
                        required
                        v-model="form.supplier_id"
                        :options="props.suppliers.map((s) => ({ label: s.name, value: s.id }))"
                        :error="form.errors.supplier_id"
                        :disabled="form.processing"
                    />
                    <SelectList
                        label="Warehouse"
                        required
                        v-model="form.warehouse_id"
                        :options="props.warehouses.map((w) => ({ label: w.name, value: w.id }))"
                        :error="form.errors.warehouse_id"
                        :disabled="form.processing"
                    />
                    <DateInput
                        type="date"
                        label="Expected Delivery"
                        v-model="form.expected_delivery_date"
                        :error="form.errors.expected_delivery_date"
                        :disabled="form.processing"
                    />
                </div>
            </Card>

            <Card>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Products</h2>
                </template>

                <ProductLineItemsGrid v-model="form.items" :products="productOptions" :columns="columns" :errors="form.errors" />

                <p v-if="form.errors.items" class="mt-2 text-sm text-red-500">{{ form.errors.items }}</p>

                <template #footer>
                    <div class="mr-auto text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Estimated total: </span>
                        <span class="font-semibold text-slate-900 dark:text-slate-100">GHS {{ estimatedTotal }}</span>
                    </div>
                    <Link :href="index().url" class="button ghost">Cancel</Link>
                    <Button type="submit" class="flex items-center gap-2" :disabled="form.processing">
                        <Loader2Icon :size="18" class="animate-spin" v-if="form.processing" />
                        <SaveIcon :size="18" v-else />
                        Create Purchase Order
                    </Button>
                </template>
            </Card>
        </form>
    </div>
</template>
