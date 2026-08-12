<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { EyeIcon } from '@lucide/vue';
import BaseTable from '@/components/DataTable/BaseTable.vue';
import Badge, { type Variant } from '@/components/ui/Badge.vue';
import { show } from '@/routes/invoices';
import type { ColumnDef } from '@/types';

interface InvoiceRow {
    id: number;
    no: string;
    source: string;
    status: string;
    grand_total: string;
    customer: { id: number; name: string };
}

defineProps<{
    invoices: InvoiceRow[];
}>();

const thead: ColumnDef[] = ['No.', 'Customer', 'Source', 'Status', 'Total (GHS)', 'Actions'];

const statusVariant: Record<string, Variant> = {
    unpaid: 'warning',
    partially_paid: 'info',
    paid: 'success',
};
</script>

<template>
    <div class="space-y-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Invoices</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Sales invoices issued from POS, van sales, and wholesale orders.</p>
        </div>

        <BaseTable :thead="thead">
            <tr v-for="invoice in invoices" :key="invoice.id" class="border-b border-slate-200 dark:border-slate-700">
                <td class="px-3 py-2 font-mono text-sm">{{ invoice.no }}</td>
                <td class="px-3 py-2">{{ invoice.customer.name }}</td>
                <td class="px-3 py-2 capitalize">{{ invoice.source.replace('_', ' ') }}</td>
                <td class="px-3 py-2">
                    <Badge :variant="statusVariant[invoice.status] ?? 'neutral'">{{ invoice.status.replace('_', ' ') }}</Badge>
                </td>
                <td class="px-3 py-2 tabular-nums">{{ invoice.grand_total }}</td>
                <td class="px-3 py-2">
                    <Link :href="show(invoice.id).url" title="View invoice">
                        <EyeIcon :size="18" class="text-slate-400 hover:text-primary-light" />
                    </Link>
                </td>
            </tr>

            <template #empty>
                <span class="font-medium text-gray-400">No invoices yet.</span>
            </template>
        </BaseTable>
    </div>
</template>
