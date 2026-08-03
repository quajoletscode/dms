<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { EyeIcon, PlusIcon } from '@lucide/vue';
import BaseTable from '@/components/DataTable/BaseTable.vue';
import Badge, { type Variant } from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import { create, show } from '@/routes/purchase-orders';
import type { ColumnDef } from '@/types';

interface PurchaseOrder {
    id: number;
    no: string;
    status: string;
    grand_total: string | number;
    supplier: { id: number; name: string };
    warehouse: { id: number; name: string };
}

defineProps<{
    purchaseOrders: PurchaseOrder[];
}>();

const thead: ColumnDef[] = ['No.', 'Supplier', 'Warehouse', 'Status', 'Total (GHS)', 'Actions'];

const statusVariant: Record<string, Variant> = {
    draft: 'neutral',
    submitted: 'info',
    approved: 'success',
    partially_received: 'warning',
    received: 'accent',
    closed: 'neutral',
    cancelled: 'danger',
};
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Purchase Orders</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Orders raised against suppliers.</p>
            </div>
            <Link :href="create().url">
                <Button class="flex items-center gap-2">
                    <PlusIcon :size="18" />
                    New Purchase Order
                </Button>
            </Link>
        </div>

        <BaseTable :thead="thead">
            <tr v-for="po in purchaseOrders" :key="po.id" class="border-b border-slate-200 dark:border-slate-700">
                <td class="px-3 py-2 font-mono text-sm">{{ po.no }}</td>
                <td class="px-3 py-2">{{ po.supplier.name }}</td>
                <td class="px-3 py-2">{{ po.warehouse.name }}</td>
                <td class="px-3 py-2">
                    <Badge :variant="statusVariant[po.status] ?? 'neutral'">{{ po.status.replace('_', ' ') }}</Badge>
                </td>
                <td class="px-3 py-2 tabular-nums">{{ po.grand_total }}</td>
                <td class="px-3 py-2">
                    <Link :href="show(po.id).url" title="View purchase order">
                        <EyeIcon :size="18" class="text-slate-400 hover:text-primary-light" />
                    </Link>
                </td>
            </tr>

            <template #empty>
                <span class="font-medium text-gray-400">No purchase orders yet.</span>
            </template>
        </BaseTable>
    </div>
</template>
