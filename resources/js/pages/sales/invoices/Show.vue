<script lang="ts" setup>
import { useForm } from '@inertiajs/vue3';
import { Loader2Icon } from '@lucide/vue';
import { ref } from 'vue';
import DetailField from '@/components/DetailField.vue';
import DetailNav from '@/components/DetailNav.vue';
import Badge, { type Variant } from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import NumberInput from '@/components/ui/inputs/NumberInput.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageNav from '@/components/ui/ObjectPageNav.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { index, show } from '@/routes/invoices';
import { store as storePayment } from '@/routes/invoices/payments';

interface InvoiceItem {
    id: number;
    product: { id: number; sku: string; name: string };
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
    return (Number(item.qty) * Number(item.unit_price) - Number(item.discount) + Number(item.tax)).toFixed(2);
}
</script>

<template>
    <div class="max-w-4xl space-y-6 py-4">
        <ObjectPageHeader
            :title="invoice.no"
            :subtitle="`${invoice.customer.name} · ${invoice.warehouse.name}`"
            :back-href="index().url"
            :status="{ label: invoice.status.replace('_', ' '), variant: statusVariant[invoice.status] ?? 'neutral' }"
        >
            <template #nav>
                <DetailNav :prev-url="prev ? show(prev).url : null" :next-url="next ? show(next).url : null" />
            </template>
            <template #actions>
                <Button v-if="canRecordPayment && invoice.status !== 'paid'" variant="ghost" @click="showPaymentForm = !showPaymentForm">
                    Record Payment
                </Button>
            </template>
        </ObjectPageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[12rem_1fr]">
            <ObjectPageNav :sections="sections" />

            <div class="space-y-6">
                <ObjectPageSection id="details" title="Details">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-4">
                        <DetailField label="Source">{{ invoice.source.replace('_', ' ') }}</DetailField>
                        <DetailField label="Due Date">{{ invoice.due_date ? new Date(invoice.due_date).toLocaleDateString() : '—' }}</DetailField>
                        <DetailField label="Subtotal">{{ invoice.subtotal }}</DetailField>
                        <DetailField label="Tax">{{ invoice.tax_total }}</DetailField>
                        <DetailField label="Discount">{{ invoice.discount_total }}</DetailField>
                        <DetailField label="Grand Total"><span class="font-semibold">{{ invoice.grand_total }}</span></DetailField>
                    </dl>
                </ObjectPageSection>

                <ObjectPageSection id="items" title="Line Items">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-900/60">
                                <tr class="text-left text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400">
                                    <th class="px-3 py-2">Product</th>
                                    <th class="px-3 py-2">Qty</th>
                                    <th class="px-3 py-2">Unit Price</th>
                                    <th class="px-3 py-2">Discount</th>
                                    <th class="px-3 py-2">Tax</th>
                                    <th class="px-3 py-2">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in invoice.items" :key="item.id" class="border-t border-slate-100 dark:border-slate-800">
                                    <td class="px-3 py-2">
                                        <p>{{ item.product.name }}</p>
                                        <p class="font-mono text-xs text-slate-400">{{ item.product.sku }}</p>
                                    </td>
                                    <td class="px-3 py-2 tabular-nums">{{ item.qty }}</td>
                                    <td class="px-3 py-2 tabular-nums">{{ item.unit_price }}</td>
                                    <td class="px-3 py-2 tabular-nums">{{ item.discount }}</td>
                                    <td class="px-3 py-2 tabular-nums">{{ item.tax }}</td>
                                    <td class="px-3 py-2 font-medium tabular-nums">{{ lineTotal(item) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </ObjectPageSection>

                <ObjectPageSection id="payments" title="Payments">
                    <form v-if="showPaymentForm" class="mb-4 space-y-4 border-b border-slate-200 pb-4 dark:border-slate-700" @submit.prevent="submitPayment">
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
                            <Button variant="ghost" type="button" @click="showPaymentForm = false">Cancel</Button>
                            <Button type="submit" class="flex items-center gap-2" :disabled="paymentForm.processing">
                                <Loader2Icon :size="18" class="animate-spin" v-if="paymentForm.processing" />
                                Save Payment
                            </Button>
                        </div>
                    </form>

                    <table v-if="invoice.payments.length" class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-900/60">
                            <tr class="text-left text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400">
                                <th class="px-3 py-2">Method</th>
                                <th class="px-3 py-2">Amount</th>
                                <th class="px-3 py-2">Reference</th>
                                <th class="px-3 py-2">Received By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="payment in invoice.payments" :key="payment.id" class="border-t border-slate-100 dark:border-slate-800">
                                <td class="px-3 py-2 capitalize">{{ payment.method.replace('_', ' ') }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ payment.amount }}</td>
                                <td class="px-3 py-2">{{ payment.reference ?? '—' }}</td>
                                <td class="px-3 py-2">{{ payment.received_by?.name ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-sm text-slate-500 dark:text-slate-400">No payments recorded yet.</p>
                </ObjectPageSection>
            </div>
        </div>
    </div>
</template>
