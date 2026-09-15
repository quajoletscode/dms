<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { BanIcon, CheckIcon, SendIcon } from '@lucide/vue';
import DetailField from '@/components/DetailField.vue';
import DetailNav from '@/components/DetailNav.vue';
import Badge, { type Variant } from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import DocumentTotalRow from '@/components/ui/DocumentTotalRow.vue';
import DocumentTotals from '@/components/ui/DocumentTotals.vue';
import FactBox from '@/components/ui/FactBox.vue';
import FactBoxRow from '@/components/ui/FactBoxRow.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageNav from '@/components/ui/ObjectPageNav.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { formatCurrency } from '@/composables/useApp';
import { useConfirm } from '@/composables/useConfirm';
import { toHumanDate } from '@/composables/useDate';
import { usePermissions } from '@/composables/usePermission';
import { computeLineTotal } from '@/lib/documentLines';
import { approve, cancel, index, show, submit } from '@/routes/purchase-orders';

interface PurchaseOrderItem {
    id: number;
    product: { id: number; sku: string; name: string };
    qty_ordered: string;
    qty_received: string;
    unit_cost: string;
    discount: string;
    tax: string;
}

interface PurchaseOrder {
    id: number;
    no: string;
    status: string;
    expected_delivery_date: string | null;
    subtotal: string;
    tax_total: string;
    discount_total: string;
    grand_total: string;
    supplier: { id: number; name: string };
    warehouse: { id: number; name: string };
    creator: { id: number; name: string } | null;
    approver: { id: number; name: string } | null;
    items: PurchaseOrderItem[];
}

const props = defineProps<{
    purchaseOrder: PurchaseOrder;
    prev: number | null;
    next: number | null;
}>();

const { can } = usePermissions();
const { Confirm } = useConfirm();

const sections = [
    { id: 'details', label: 'Details' },
    { id: 'items', label: 'Line Items' },
];

const statusVariant: Record<string, Variant> = {
    draft: 'neutral',
    submitted: 'info',
    approved: 'success',
    partially_received: 'warning',
    received: 'accent',
    closed: 'neutral',
    cancelled: 'danger',
};

function lineTotal(item: PurchaseOrderItem): string {
    return computeLineTotal({
        qty: item.qty_ordered,
        unitPrice: item.unit_cost,
        discount: item.discount,
        tax: item.tax,
    }).toFixed(2);
}

function doSubmit() {
    router.post(submit(props.purchaseOrder.id).url);
}

function doApprove() {
    router.post(approve(props.purchaseOrder.id).url);
}

async function doCancel() {
    const confirmed = await Confirm({
        title: 'Cancel purchase order?',
        message: `This will cancel ${props.purchaseOrder.no}. This cannot be undone.`,
        confirmText: 'Cancel Order',
        cancelText: 'Keep it',
    });

    if (confirmed) {
        router.post(cancel(props.purchaseOrder.id).url);
    }
}
</script>

<template>
    <div class="max-w-6xl space-y-6 py-4">
        <ObjectPageHeader
            :title="purchaseOrder.no"
            :subtitle="`${purchaseOrder.supplier.name} → ${purchaseOrder.warehouse.name}`"
            :back-href="index().url"
            :status="{
                label: purchaseOrder.status.replace('_', ' '),
                variant: statusVariant[purchaseOrder.status] ?? 'neutral',
            }"
        >
            <template #nav>
                <DetailNav
                    :prev-url="props.prev ? show(props.prev).url : null"
                    :next-url="props.next ? show(props.next).url : null"
                />
            </template>
            <template #actions>
                <Button
                    v-if="purchaseOrder.status === 'draft' && can('po.create')"
                    variant="ghost"
                    class="flex items-center gap-2"
                    @click="doSubmit"
                >
                    <SendIcon :size="16" />
                    Submit
                </Button>
                <Button
                    v-if="
                        purchaseOrder.status === 'submitted' &&
                        can('po.approve')
                    "
                    variant="green"
                    class="flex items-center gap-2"
                    @click="doApprove"
                >
                    <CheckIcon :size="16" />
                    Approve
                </Button>
                <Button
                    v-if="
                        ['draft', 'submitted', 'approved'].includes(
                            purchaseOrder.status,
                        ) && can('po.create')
                    "
                    variant="danger"
                    class="flex items-center gap-2"
                    @click="doCancel"
                >
                    <BanIcon :size="16" />
                    Cancel
                </Button>
            </template>
        </ObjectPageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[12rem_1fr_16rem]">
            <ObjectPageNav :sections="sections" />

            <div class="space-y-6">
                <ObjectPageSection id="details" title="Details" collapsible>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-3">
                        <DetailField label="Expected Delivery">
                            {{
                                toHumanDate(
                                    purchaseOrder.expected_delivery_date,
                                )
                            }}
                        </DetailField>
                        <DetailField label="Created By">{{
                            purchaseOrder.creator?.name ?? '—'
                        }}</DetailField>
                        <DetailField label="Approved By">{{
                            purchaseOrder.approver?.name ?? '—'
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
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-4 py-3">Qty Ordered</th>
                                    <th class="px-4 py-3">Qty Received</th>
                                    <th class="px-4 py-3">Unit Cost</th>
                                    <th class="px-4 py-3">Discount</th>
                                    <th class="px-4 py-3">Tax</th>
                                    <th class="px-4 py-3">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in purchaseOrder.items"
                                    :key="item.id"
                                    class="border-t border-slate-100 dark:border-slate-800"
                                >
                                    <td class="px-4 py-2.5">
                                        <p>{{ item.product.name }}</p>
                                        <p
                                            class="font-mono text-xs text-slate-400"
                                        >
                                            {{ item.product.sku }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-2.5 tabular-nums">
                                        {{ item.qty_ordered }}
                                    </td>
                                    <td class="px-4 py-2.5 tabular-nums">
                                        {{ item.qty_received }}
                                    </td>
                                    <td class="px-4 py-2.5 tabular-nums">
                                        {{ item.unit_cost }}
                                    </td>
                                    <td class="px-4 py-2.5 tabular-nums">
                                        {{ item.discount }}
                                    </td>
                                    <td class="px-4 py-2.5 tabular-nums">
                                        {{ item.tax }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 font-medium tabular-nums"
                                    >
                                        {{ lineTotal(item) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <DocumentTotals class="mt-4 ml-auto max-w-xs">
                        <DocumentTotalRow label="Subtotal">{{
                            formatCurrency(Number(purchaseOrder.subtotal))
                        }}</DocumentTotalRow>
                        <DocumentTotalRow label="Discount">{{
                            formatCurrency(
                                Number(purchaseOrder.discount_total),
                            )
                        }}</DocumentTotalRow>
                        <DocumentTotalRow label="Tax">{{
                            formatCurrency(Number(purchaseOrder.tax_total))
                        }}</DocumentTotalRow>
                        <DocumentTotalRow label="Grand Total" emphasized>{{
                            formatCurrency(Number(purchaseOrder.grand_total))
                        }}</DocumentTotalRow>
                    </DocumentTotals>
                </ObjectPageSection>
            </div>

            <FactBox title="Document Info">
                <FactBoxRow label="Supplier">{{
                    purchaseOrder.supplier.name
                }}</FactBoxRow>
                <FactBoxRow label="Warehouse">{{
                    purchaseOrder.warehouse.name
                }}</FactBoxRow>
                <FactBoxRow label="Status">
                    <Badge
                        :variant="statusVariant[purchaseOrder.status] ?? 'neutral'"
                        >{{ purchaseOrder.status.replace('_', ' ') }}</Badge
                    >
                </FactBoxRow>
            </FactBox>
        </div>
    </div>
</template>
