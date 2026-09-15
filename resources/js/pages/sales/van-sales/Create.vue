<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import { computed } from 'vue';
import DebtorBillLineItemsGrid, {
    type DebtorBillRow,
    type ProductOption,
} from '@/components/DebtorBillLineItemsGrid.vue';
import PaymentsGrid, { type PaymentRow } from '@/components/PaymentsGrid.vue';
import Button from '@/components/ui/Button.vue';
import DocumentTotalRow from '@/components/ui/DocumentTotalRow.vue';
import DocumentTotals from '@/components/ui/DocumentTotals.vue';
import DateInput from '@/components/ui/inputs/DateInput.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageNav from '@/components/ui/ObjectPageNav.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { formatCurrency } from '@/composables/useApp';
import { index as invoicesIndex } from '@/routes/invoices';
import { store } from '@/routes/van-sales';

const props = defineProps<{
    ownVan?: { id: number; code: string };
    vans: Array<{
        id: number;
        code: string;
        dsr: { id: number; name: string } | null;
    }>;
    customers: Array<{
        id: number;
        name: string;
        credit_limit: string;
        driver_vehicle_profiles: Array<{ vehicle_no: string | null }>;
    }>;
    products: Array<{
        id: number;
        sku: string;
        name: string;
        van_price: string;
        tax_rate: string;
    }>;
}>();

// A manager acting on behalf of a DSR has no van of their own — they must
// pick one from the full list instead.
const needsVanPicker = !props.ownVan;

const sections = [
    ...(needsVanPicker ? [{ id: 'van', label: 'Van' }] : []),
    { id: 'customer', label: 'Customer' },
    { id: 'items', label: 'Bill' },
    { id: 'payments', label: 'Payments' },
];

const today = () => {
    const date = new Date();
    const offsetDate = new Date(
        date.getTime() - date.getTimezoneOffset() * 60000,
    );

    return offsetDate.toISOString().slice(0, 10);
};

const form = useForm<{
    sale_date: string;
    van_storage_id: string | number;
    customer_id: string | number;
    items: DebtorBillRow[];
    payments: PaymentRow[];
}>({
    sale_date: today(),
    van_storage_id: props.ownVan?.id ?? '',
    customer_id: '',
    items: [],
    payments: [],
});

const productOptions = computed<ProductOption[]>(() =>
    props.products.map((product) => ({
        id: product.id,
        label: `${product.sku} — ${product.name}`,
        defaults: { unit_price: product.van_price },
    })),
);

const selectedCustomer = computed(() =>
    props.customers.find(
        (customer) => customer.id === Number(form.customer_id),
    ),
);

const filledItems = computed(() =>
    form.items.filter((row) => row.product_id !== '' && row.qty !== ''),
);

const grandTotal = computed(() =>
    filledItems.value.reduce((sum, row) => {
        const qty = Number(row.qty) || 0;
        const unitPrice = Number(row.unit_price) || 0;
        const discount = Number(row.discount) || 0;

        return sum + qty * unitPrice - discount;
    }, 0),
);

const totalPayments = computed(() =>
    form.payments.reduce(
        (sum, payment) => sum + (Number(payment.amount) || 0),
        0,
    ),
);

const balanceDue = computed(() => grandTotal.value - totalPayments.value);

const submit = () => {
    form.transform((data) => ({
        ...data,
        sale_date: form.sale_date,
        items: filledItems.value,
    })).post(store().url);
};
</script>

<template>
    <div class="max-w-4xl space-y-6 py-4">
        <ObjectPageHeader
            title="Record Van Sale"
            :back-href="invoicesIndex().url"
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[12rem_1fr]">
            <ObjectPageNav :sections="sections" />

            <form class="space-y-6" @submit.prevent="submit">
                <ObjectPageSection v-if="needsVanPicker" id="van" title="Van">
                    <SelectList
                        label="Van"
                        required
                        v-model="form.van_storage_id"
                        :options="
                            props.vans.map((van) => ({
                                label: `${van.code} — ${van.dsr?.name ?? 'Unassigned'}`,
                                value: van.id,
                            }))
                        "
                        :error="form.errors.van_storage_id"
                        :disabled="form.processing"
                    />
                </ObjectPageSection>

                <ObjectPageSection id="customer" title="Customer">
                    <div
                        class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_12rem]"
                    >
                        <SelectList
                            label="Customer"
                            required
                            v-model="form.customer_id"
                            :options="
                                props.customers.map((c) => ({
                                    label: c.name,
                                    value: c.id,
                                }))
                            "
                            :error="form.errors.customer_id"
                            :disabled="form.processing"
                        />
                        <DateInput
                            type="date"
                            label="Date"
                            required
                            v-model="form.sale_date"
                            :error="form.errors.sale_date"
                            :disabled="form.processing"
                        />
                    </div>
                </ObjectPageSection>

                <ObjectPageSection id="items" title="Bill">
                    <DebtorBillLineItemsGrid
                        v-model="form.items"
                        :sale-date="form.sale_date"
                        :products="productOptions"
                        :vehicles="
                            selectedCustomer?.driver_vehicle_profiles ?? []
                        "
                        :errors="form.errors"
                    />
                    <p
                        v-if="form.errors.items"
                        class="mt-2 text-sm text-red-500"
                    >
                        {{ form.errors.items }}
                    </p>
                </ObjectPageSection>

                <ObjectPageSection id="payments" title="Payments">
                    <PaymentsGrid
                        v-model="form.payments"
                        :errors="form.errors"
                    />

                    <template #footer>
                        <DocumentTotals class="mr-auto max-w-xs">
                            <DocumentTotalRow label="Total">{{
                                formatCurrency(grandTotal)
                            }}</DocumentTotalRow>
                            <DocumentTotalRow label="Balance Due" emphasized>{{
                                formatCurrency(balanceDue)
                            }}</DocumentTotalRow>
                        </DocumentTotals>
                        <Link :href="invoicesIndex().url" class="button ghost"
                            >Cancel</Link
                        >
                        <Button
                            type="submit"
                            class="flex items-center gap-2"
                            :disabled="form.processing"
                        >
                            <Loader2Icon
                                :size="18"
                                class="animate-spin"
                                v-if="form.processing"
                            />
                            <SaveIcon :size="18" v-else />
                            Complete Sale
                        </Button>
                    </template>
                </ObjectPageSection>
            </form>
        </div>
    </div>
</template>
