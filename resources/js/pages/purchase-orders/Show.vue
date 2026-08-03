<script lang="ts" setup>
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeftIcon, BanIcon, CheckIcon, SendIcon } from '@lucide/vue';
import DetailField from '@/components/DetailField.vue';
import DetailNav from '@/components/DetailNav.vue';
import Badge, { type Variant } from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import { usePermissions } from '@/composables/usePermission';
import { useConfirm } from '@/composables/useConfirm';
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
    return (Number(item.qty_ordered) * Number(item.unit_cost) - Number(item.discount) + Number(item.tax)).toFixed(2);
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
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link :href="index().url" class="text-slate-400 hover:text-primary-light" title="Back to purchase orders">
                    <ArrowLeftIcon :size="20" />
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-mono text-2xl font-bold text-slate-900 dark:text-slate-100">{{ purchaseOrder.no }}</h1>
                        <Badge :variant="statusVariant[purchaseOrder.status] ?? 'neutral'">{{ purchaseOrder.status.replace('_', ' ') }}</Badge>
                    </div>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ purchaseOrder.supplier.name }} → {{ purchaseOrder.warehouse.name }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <DetailNav :prev-url="props.prev ? show(props.prev).url : null" :next-url="props.next ? show(props.next).url : null" />

                <Button v-if="purchaseOrder.status === 'draft' && can('po.create')" variant="ghost" class="flex items-center gap-2" @click="doSubmit">
                    <SendIcon :size="16" />
                    Submit
                </Button>
                <Button v-if="purchaseOrder.status === 'submitted' && can('po.approve')" variant="green" class="flex items-center gap-2" @click="doApprove">
                    <CheckIcon :size="16" />
                    Approve
                </Button>
                <Button
                    v-if="['draft', 'submitted', 'approved'].includes(purchaseOrder.status) && can('po.create')"
                    variant="danger"
                    class="flex items-center gap-2"
                    @click="doCancel"
                >
                    <BanIcon :size="16" />
                    Cancel
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <Card>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Details</h2>
                </template>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-5">
                    <DetailField label="Supplier">{{ purchaseOrder.supplier.name }}</DetailField>
                    <DetailField label="Warehouse">{{ purchaseOrder.warehouse.name }}</DetailField>
                    <DetailField label="Expected Delivery">
                        {{ purchaseOrder.expected_delivery_date ? new Date(purchaseOrder.expected_delivery_date).toLocaleDateString() : '—' }}
                    </DetailField>
                    <DetailField label="Created By">{{ purchaseOrder.creator?.name ?? '—' }}</DetailField>
                    <DetailField label="Approved By">{{ purchaseOrder.approver?.name ?? '—' }}</DetailField>
                </dl>
            </Card>

            <Card class="lg:col-span-2">
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Totals (GHS)</h2>
                </template>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-4">
                    <DetailField label="Subtotal">{{ purchaseOrder.subtotal }}</DetailField>
                    <DetailField label="Tax">{{ purchaseOrder.tax_total }}</DetailField>
                    <DetailField label="Discount">{{ purchaseOrder.discount_total }}</DetailField>
                    <DetailField label="Grand Total">
                        <span class="font-semibold">{{ purchaseOrder.grand_total }}</span>
                    </DetailField>
                </dl>
            </Card>
        </div>

        <Card padding="none">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-900/60">
                        <tr class="text-left text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400">
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
                        <tr v-for="item in purchaseOrder.items" :key="item.id" class="border-t border-slate-100 dark:border-slate-800">
                            <td class="px-4 py-2.5">
                                <p>{{ item.product.name }}</p>
                                <p class="font-mono text-xs text-slate-400">{{ item.product.sku }}</p>
                            </td>
                            <td class="px-4 py-2.5 tabular-nums">{{ item.qty_ordered }}</td>
                            <td class="px-4 py-2.5 tabular-nums">{{ item.qty_received }}</td>
                            <td class="px-4 py-2.5 tabular-nums">{{ item.unit_cost }}</td>
                            <td class="px-4 py-2.5 tabular-nums">{{ item.discount }}</td>
                            <td class="px-4 py-2.5 tabular-nums">{{ item.tax }}</td>
                            <td class="px-4 py-2.5 font-medium tabular-nums">{{ lineTotal(item) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
