<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import { computed } from 'vue';
import PaymentsGrid, { type PaymentRow } from '@/components/PaymentsGrid.vue';
import ProductLineItemsGrid, { type LineItemColumn, type LineItemRow, type ProductOption } from '@/components/ProductLineItemsGrid.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageNav from '@/components/ui/ObjectPageNav.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { store } from '@/routes/pos';
import { index as tillSessionsIndex } from '@/routes/till-sessions';

const props = defineProps<{
    tillSession: { id: number; warehouse_id: number };
    customers: Array<{ id: number; name: string; credit_limit: string }>;
    products: Array<{ id: number; sku: string; name: string; retail_price: string; tax_rate: string }>;
    discountOverrideThresholdPercent: number;
    canOverrideDiscount: boolean;
}>();

const sections = [
    { id: 'customer', label: 'Customer' },
    { id: 'items', label: 'Items' },
    { id: 'payments', label: 'Payments' },
];

const form = useForm<{
    customer_id: string | number;
    items: LineItemRow[];
    payments: PaymentRow[];
}>({
    customer_id: '',
    items: [],
    payments: [],
});

const productOptions = computed<ProductOption[]>(() =>
    props.products.map((product) => ({
        id: product.id,
        label: `${product.sku} — ${product.name}`,
        defaults: { unit_price: product.retail_price },
    })),
);

const columns: LineItemColumn[] = [
    { key: 'qty', label: 'Qty' },
    { key: 'unit_price', label: 'Unit Price (GHS)' },
    { key: 'discount', label: 'Discount' },
];

const filledItems = computed(() => form.items.filter((row) => row.product_id !== '' && row.qty !== ''));

const grandTotal = computed(() =>
    filledItems.value
        .reduce((sum, row) => {
            const qty = Number(row.qty) || 0;
            const unitPrice = Number(row.unit_price) || 0;
            const discount = Number(row.discount) || 0;

            return sum + qty * unitPrice - discount;
        }, 0)
        .toFixed(2),
);

const totalPayments = computed(() => form.payments.reduce((sum, payment) => sum + (Number(payment.amount) || 0), 0).toFixed(2));

const balanceDue = computed(() => (Number(grandTotal.value) - Number(totalPayments.value)).toFixed(2));

interface OverThresholdLine {
    product: string;
    ratePercent: number;
}

const overThresholdLines = computed<OverThresholdLine[]>(() =>
    filledItems.value.reduce<OverThresholdLine[]>((lines, row) => {
        const qty = Number(row.qty) || 0;
        const unitPrice = Number(row.unit_price) || 0;
        const discount = Number(row.discount) || 0;
        const lineGross = qty * unitPrice;

        if (lineGross <= 0 || discount <= 0) {
            return lines;
        }

        const ratePercent = (discount / lineGross) * 100;

        if (ratePercent > props.discountOverrideThresholdPercent) {
            const product = props.products.find((p) => p.id === Number(row.product_id));
            lines.push({ product: product?.name ?? `Product #${row.product_id}`, ratePercent: Math.round(ratePercent) });
        }

        return lines;
    }, []),
);

const canSubmit = computed(() => overThresholdLines.value.length === 0 || props.canOverrideDiscount);

const submit = () => {
    form.transform((data) => ({
        ...data,
        items: filledItems.value,
    })).post(store().url);
};
</script>

<template>
    <div class="max-w-4xl space-y-6 py-4">
        <ObjectPageHeader title="Ring Up Sale" subtitle="POS" :back-href="tillSessionsIndex().url" />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[12rem_1fr]">
            <ObjectPageNav :sections="sections" />

            <form class="space-y-6" @submit.prevent="submit">
                <ObjectPageSection id="customer" title="Customer">
                    <SelectList
                        label="Customer"
                        required
                        v-model="form.customer_id"
                        :options="props.customers.map((c) => ({ label: c.name, value: c.id }))"
                        :error="form.errors.customer_id"
                        :disabled="form.processing"
                    />
                </ObjectPageSection>

                <ObjectPageSection id="items" title="Items">
                    <ProductLineItemsGrid v-model="form.items" :products="productOptions" :columns="columns" :errors="form.errors" />
                    <p v-if="form.errors.items" class="mt-2 text-sm text-red-500">{{ form.errors.items }}</p>

                    <div
                        v-if="overThresholdLines.length"
                        class="mt-4 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300"
                    >
                        <p class="font-medium">Discount above {{ discountOverrideThresholdPercent }}% threshold:</p>
                        <ul class="mt-1 list-inside list-disc">
                            <li v-for="line in overThresholdLines" :key="line.product">{{ line.product }} — {{ line.ratePercent }}%</li>
                        </ul>
                        <p v-if="!canOverrideDiscount" class="mt-1">You don't have permission to override this — reduce the discount or ask for approval.</p>
                        <Badge v-else variant="warning" class="mt-2">Will be recorded with your override permission</Badge>
                    </div>
                </ObjectPageSection>

                <ObjectPageSection id="payments" title="Payments">
                    <PaymentsGrid v-model="form.payments" :errors="form.errors" />

                    <template #footer>
                        <div class="mr-auto space-y-0.5 text-sm">
                            <p>
                                <span class="text-slate-500 dark:text-slate-400">Total: </span
                                ><span class="font-semibold text-slate-900 dark:text-slate-100">GHS {{ grandTotal }}</span>
                            </p>
                            <p>
                                <span class="text-slate-500 dark:text-slate-400">Balance due: </span
                                ><span class="font-semibold text-slate-900 dark:text-slate-100">GHS {{ balanceDue }}</span>
                            </p>
                        </div>
                        <Link :href="tillSessionsIndex().url" class="button ghost">Cancel</Link>
                        <Button type="submit" class="flex items-center gap-2" :disabled="form.processing || !canSubmit">
                            <Loader2Icon :size="18" class="animate-spin" v-if="form.processing" />
                            <SaveIcon :size="18" v-else />
                            Complete Sale
                        </Button>
                    </template>
                </ObjectPageSection>
            </form>
        </div>
    </div>
</template>
