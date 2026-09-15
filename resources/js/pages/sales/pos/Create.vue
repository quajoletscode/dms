<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import { computed } from 'vue';
import DebtorBillLineItemsGrid, {
    type DebtorBillRow,
    type ProductOption,
} from '@/components/DebtorBillLineItemsGrid.vue';
import PaymentsGrid, { type PaymentRow } from '@/components/PaymentsGrid.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import DocumentTotalRow from '@/components/ui/DocumentTotalRow.vue';
import DocumentTotals from '@/components/ui/DocumentTotals.vue';
import DateInput from '@/components/ui/inputs/DateInput.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageNav from '@/components/ui/ObjectPageNav.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { formatCurrency } from '@/composables/useApp';
import { store } from '@/routes/pos';
import { index as tillSessionsIndex } from '@/routes/till-sessions';

const props = defineProps<{
    tillSession: { id: number; warehouse_id: number };
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
        retail_price: string;
        tax_rate: string;
    }>;
    discountOverrideThresholdPercent: number;
    canOverrideDiscount: boolean;
}>();

const sections = [
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
    customer_id: string | number;
    items: DebtorBillRow[];
    payments: PaymentRow[];
}>({
    sale_date: today(),
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
            const product = props.products.find(
                (p) => p.id === Number(row.product_id),
            );
            lines.push({
                product: product?.name ?? `Product #${row.product_id}`,
                ratePercent: Math.round(ratePercent),
            });
        }

        return lines;
    }, []),
);

const canSubmit = computed(
    () => overThresholdLines.value.length === 0 || props.canOverrideDiscount,
);

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
            title="Ring Up Sale"
            subtitle="POS"
            :back-href="tillSessionsIndex().url"
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[12rem_1fr]">
            <ObjectPageNav :sections="sections" />

            <form class="space-y-6" @submit.prevent="submit">
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

                    <div
                        v-if="overThresholdLines.length"
                        class="mt-4 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300"
                    >
                        <p class="font-medium">
                            Discount above
                            {{ discountOverrideThresholdPercent }}% threshold:
                        </p>
                        <ul class="mt-1 list-inside list-disc">
                            <li
                                v-for="line in overThresholdLines"
                                :key="line.product"
                            >
                                {{ line.product }} — {{ line.ratePercent }}%
                            </li>
                        </ul>
                        <p v-if="!canOverrideDiscount" class="mt-1">
                            You don't have permission to override this — reduce
                            the discount or ask for approval.
                        </p>
                        <Badge v-else variant="warning" class="mt-2"
                            >Will be recorded with your override
                            permission</Badge
                        >
                    </div>
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
                        <Link
                            :href="tillSessionsIndex().url"
                            class="button ghost"
                            >Cancel</Link
                        >
                        <Button
                            type="submit"
                            class="flex items-center gap-2"
                            :disabled="form.processing || !canSubmit"
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
