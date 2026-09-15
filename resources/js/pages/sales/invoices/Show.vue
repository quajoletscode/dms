<script lang="ts" setup>
import { useForm } from '@inertiajs/vue3';
import { Loader2Icon } from '@lucide/vue';
import { computed, ref } from 'vue';
import DetailField from '@/components/DetailField.vue';
import DetailNav from '@/components/DetailNav.vue';
import Badge, { type Variant } from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import DocumentTotalRow from '@/components/ui/DocumentTotalRow.vue';
import DocumentTotals from '@/components/ui/DocumentTotals.vue';
import FactBox from '@/components/ui/FactBox.vue';
import FactBoxRow from '@/components/ui/FactBoxRow.vue';
import NumberInput from '@/components/ui/inputs/NumberInput.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageNav from '@/components/ui/ObjectPageNav.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { formatCurrency } from '@/composables/useApp';
import { toHumanDate } from '@/composables/useDate';
import { computeLineTotal } from '@/lib/documentLines';
import { index, show } from '@/routes/invoices';
import { store as storePayment } from '@/routes/invoices/payments';

interface InvoiceItem {
    id: number;
    line_type: 'item' | 'gl_account' | 'comment';
    product: { id: number; sku: string; name: string } | null;
    gl_account: { id: number; code: string; name: string } | null;
    service_date: string | null;
    vehicle_no: string | null;
    line_description: string | null;
    qty: string;
    unit_price: string;
    discount: string;
    tax: string;
}

interface InvoicePayment {
    id: number;
    method: string;
    amount: string;
    reference: string | null;
    received_by: { id: number; name: string } | null;
    paid_at: string | null;
}

interface Invoice {
    id: number;
    no: string;
    source: string;
    status: string;
    due_date: string | null;
    invoice_date: string | null;
    posting_date: string | null;
    subtotal: string;
    tax_total: string;
    discount_total: string;
    grand_total: string;
    customer: { id: number; name: string };
    warehouse: { id: number; name: string };
    items: InvoiceItem[];
    payments: InvoicePayment[];
}

const props = defineProps<{
    invoice: Invoice;
    canRecordPayment: boolean;
    prev: number | null;
    next: number | null;
}>();

const sections = [
    { id: 'details', label: 'Details' },
    { id: 'items', label: 'Line Items' },
    { id: 'payments', label: 'Payments' },
];

const statusVariant: Record<string, Variant> = {
    unpaid: 'warning',
    partially_paid: 'info',
    paid: 'success',
};

const lineTypeLabel: Record<InvoiceItem['line_type'], string> = {
    item: 'Item',
    gl_account: 'G/L Account',
    comment: 'Comment',
};

const lineTypeVariant: Record<InvoiceItem['line_type'], Variant> = {
    item: 'neutral',
    gl_account: 'accent',
    comment: 'info',
};

const methodOptions = [
    { label: 'Cash', value: 'cash' },
    { label: 'Mobile Money', value: 'mobile_money' },
    { label: 'Card', value: 'card' },
    { label: 'Bank Transfer', value: 'bank_transfer' },
];

const showPaymentForm = ref(false);

const paymentForm = useForm({
    amount: '',
    method: 'cash',
    reference: '',
});

function submitPayment() {
    paymentForm.post(storePayment(props.invoice.id).url, {
        onSuccess: () => {
            paymentForm.reset();
            showPaymentForm.value = false;
        },
    });
}

function lineTotal(item: InvoiceItem): string {
    return computeLineTotal({
        qty: item.qty,
        unitPrice: item.unit_price,
        discount: item.discount,
        tax: item.tax,
    }).toFixed(2);
}

const balanceDue = computed(() => {
    const paid = props.invoice.payments.reduce(
        (sum, payment) => sum + Number(payment.amount),
        0,
    );

    return Number(props.invoice.grand_total) - paid;
});
</script>

<template>
    <div class="max-w-6xl space-y-6 py-4">
        <ObjectPageHeader
            :title="invoice.no"
            :subtitle="`${invoice.customer.name} · ${invoice.warehouse.name}`"
            :back-href="index().url"
            :status="{
                label: invoice.status.replace('_', ' '),
                variant: statusVariant[invoice.status] ?? 'neutral',
            }"
        >
            <template #nav>
                <DetailNav
                    :prev-url="prev ? show(prev).url : null"
                    :next-url="next ? show(next).url : null"
                />
            </template>
            <template #actions>
                <Button
                    v-if="canRecordPayment && invoice.status !== 'paid'"
                    variant="ghost"
                    @click="showPaymentForm = !showPaymentForm"
                >
                    Record Payment
                </Button>
            </template>
        </ObjectPageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[12rem_1fr_16rem]">
            <ObjectPageNav :sections="sections" />

            <div class="space-y-6">
                <ObjectPageSection id="details" title="Details" collapsible>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-4">
                        <DetailField label="Source">{{
                            invoice.source.replace('_', ' ')
                        }}</DetailField>
                        <DetailField label="Document Date">{{
                            toHumanDate(invoice.invoice_date)
                        }}</DetailField>
                        <DetailField label="Posting Date">{{
                            toHumanDate(invoice.posting_date)
                        }}</DetailField>
                        <DetailField label="Due Date">{{
                            toHumanDate(invoice.due_date)
                        }}</DetailField>
                    </dl>
                </ObjectPageSection>

                <ObjectPageSection id="items" title="Line Items" collapsible>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-900/60">
                                <tr
                                    class="text-left text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400"
                                >
                                    <th class="px-3 py-2">Type</th>
                                    <th class="px-3 py-2">Product / Account</th>
                                    <th class="px-3 py-2">Date</th>
                                    <th class="px-3 py-2">Vehicle</th>
                                    <th class="px-3 py-2">Description</th>
                                    <th class="px-3 py-2">Qty</th>
                                    <th class="px-3 py-2">Unit Price</th>
                                    <th class="px-3 py-2">Discount</th>
                                    <th class="px-3 py-2">Tax</th>
                                    <th class="px-3 py-2">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in invoice.items"
                                    :key="item.id"
                                    class="border-t border-slate-100 dark:border-slate-800"
                                >
                                    <td class="px-3 py-2 align-top">
                                        <Badge
                                            :variant="
                                                lineTypeVariant[item.line_type]
                                            "
                                            >{{
                                                lineTypeLabel[item.line_type]
                                            }}</Badge
                                        >
                                    </td>
                                    <template
                                        v-if="item.line_type === 'comment'"
                                    >
                                        <td class="px-3 py-2" colspan="8">
                                            {{ item.line_description ?? '—' }}
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td class="px-3 py-2">
                                            <template
                                                v-if="
                                                    item.line_type ===
                                                    'gl_account'
                                                "
                                            >
                                                <p>
                                                    {{ item.gl_account?.name }}
                                                </p>
                                                <p
                                                    class="font-mono text-xs text-slate-400"
                                                >
                                                    {{ item.gl_account?.code }}
                                                </p>
                                            </template>
                                            <template v-else>
                                                <p>{{ item.product?.name }}</p>
                                                <p
                                                    class="font-mono text-xs text-slate-400"
                                                >
                                                    {{ item.product?.sku }}
                                                </p>
                                            </template>
                                        </td>
                                        <td class="px-3 py-2 tabular-nums">
                                            {{ item.service_date ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ item.vehicle_no ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ item.line_description ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2 tabular-nums">
                                            {{
                                                item.line_type === 'gl_account'
                                                    ? '—'
                                                    : item.qty
                                            }}
                                        </td>
                                        <td class="px-3 py-2 tabular-nums">
                                            {{ item.unit_price }}
                                        </td>
                                        <td class="px-3 py-2 tabular-nums">
                                            {{ item.discount }}
                                        </td>
                                        <td class="px-3 py-2 tabular-nums">
                                            {{ item.tax }}
                                        </td>
                                        <td
                                            class="px-3 py-2 font-medium tabular-nums"
                                        >
                                            {{ lineTotal(item) }}
                                        </td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <DocumentTotals class="mt-4 ml-auto max-w-xs">
                        <DocumentTotalRow label="Subtotal">{{
                            formatCurrency(Number(invoice.subtotal))
                        }}</DocumentTotalRow>
                        <DocumentTotalRow label="Discount">{{
                            formatCurrency(Number(invoice.discount_total))
                        }}</DocumentTotalRow>
                        <DocumentTotalRow label="Tax">{{
                            formatCurrency(Number(invoice.tax_total))
                        }}</DocumentTotalRow>
                        <DocumentTotalRow label="Grand Total" emphasized>{{
                            formatCurrency(Number(invoice.grand_total))
                        }}</DocumentTotalRow>
                    </DocumentTotals>
                </ObjectPageSection>

                <ObjectPageSection id="payments" title="Payments" collapsible>
                    <form
                        v-if="showPaymentForm"
                        class="mb-4 space-y-4 border-b border-slate-200 pb-4 dark:border-slate-700"
                        @submit.prevent="submitPayment"
                    >
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <NumberInput
                                label="Amount"
                                required
                                v-model="paymentForm.amount"
                                :error="paymentForm.errors.amount"
                                :disabled="paymentForm.processing"
                            />
                            <SelectList
                                label="Method"
                                required
                                v-model="paymentForm.method"
                                :options="methodOptions"
                                :error="paymentForm.errors.method"
                                :disabled="paymentForm.processing"
                            />
                            <TextInput
                                label="Reference"
                                v-model="paymentForm.reference"
                                :error="paymentForm.errors.reference"
                                :disabled="paymentForm.processing"
                            />
                        </div>
                        <div class="flex justify-end gap-3">
                            <Button
                                variant="ghost"
                                type="button"
                                @click="showPaymentForm = false"
                                >Cancel</Button
                            >
                            <Button
                                type="submit"
                                class="flex items-center gap-2"
                                :disabled="paymentForm.processing"
                            >
                                <Loader2Icon
                                    :size="18"
                                    class="animate-spin"
                                    v-if="paymentForm.processing"
                                />
                                Save Payment
                            </Button>
                        </div>
                    </form>

                    <table
                        v-if="invoice.payments.length"
                        class="w-full text-sm"
                    >
                        <thead class="bg-slate-50 dark:bg-slate-900/60">
                            <tr
                                class="text-left text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400"
                            >
                                <th class="px-3 py-2">Method</th>
                                <th class="px-3 py-2">Amount</th>
                                <th class="px-3 py-2">Reference</th>
                                <th class="px-3 py-2">Received By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="payment in invoice.payments"
                                :key="payment.id"
                                class="border-t border-slate-100 dark:border-slate-800"
                            >
                                <td class="px-3 py-2 capitalize">
                                    {{ payment.method.replace('_', ' ') }}
                                </td>
                                <td class="px-3 py-2 tabular-nums">
                                    {{ payment.amount }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ payment.reference ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ payment.received_by?.name ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p
                        v-else
                        class="text-sm text-slate-500 dark:text-slate-400"
                    >
                        No payments recorded yet.
                    </p>
                </ObjectPageSection>
            </div>

            <FactBox title="Document Info">
                <FactBoxRow label="Customer">{{
                    invoice.customer.name
                }}</FactBoxRow>
                <FactBoxRow label="Warehouse">{{
                    invoice.warehouse.name
                }}</FactBoxRow>
                <FactBoxRow label="Payment Status">
                    <Badge :variant="statusVariant[invoice.status] ?? 'neutral'">{{
                        invoice.status.replace('_', ' ')
                    }}</Badge>
                </FactBoxRow>
                <FactBoxRow label="Balance Due">{{
                    formatCurrency(balanceDue)
                }}</FactBoxRow>
            </FactBox>
        </div>
    </div>
</template>
