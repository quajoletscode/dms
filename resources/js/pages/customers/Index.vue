<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { EyeIcon, PlusIcon, SquarePenIcon } from '@lucide/vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import { create, edit, show } from '@/routes/customers';
import type { ColumnDef, SimplePaginationMeta } from '@/types';

interface Customer {
    id: number;
    code: string;
    name: string;
    type: string;
    is_active: boolean;
}

defineProps<{
    customers: Customer[];
    meta?: SimplePaginationMeta;
}>();

const thead: ColumnDef[] = [
    { label: 'Code', key: 'code' },
    { label: 'Name', key: 'name' },
    { label: 'Type', key: 'type' },
    'Status',
    'Actions',
];
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1
                    class="text-2xl font-bold text-slate-900 dark:text-slate-100"
                >
                    Customers
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Wholesale customers and retailers.
                </p>
            </div>
            <Link :href="create().url">
                <Button class="flex items-center gap-2">
                    <PlusIcon :size="18" />
                    New Customer
                </Button>
            </Link>
        </div>

        <DataTable
            :items="customers"
            :thead="thead"
            :is-loading="false"
            :meta="meta"
            :only="['customers', 'meta', 'request']"
            search-placeholder="Search customers..."
        >
            <template #default="{ items }">
                <tr
                    v-for="customer in items"
                    :key="customer.id"
                    class="border-b border-slate-200 dark:border-slate-700"
                >
                    <td class="px-3 py-2 font-mono text-sm">
                        {{ customer.code }}
                    </td>
                    <td class="px-3 py-2">{{ customer.name }}</td>
                    <td
                        class="px-3 py-2 text-slate-500 capitalize dark:text-slate-400"
                    >
                        {{ customer.type }}
                    </td>
                    <td class="px-3 py-2">
                        <Badge
                            :variant="
                                customer.is_active ? 'success' : 'neutral'
                            "
                            >{{
                                customer.is_active ? 'Active' : 'Inactive'
                            }}</Badge
                        >
                    </td>
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-3">
                            <Link
                                :href="show(customer.id).url"
                                title="View customer"
                            >
                                <EyeIcon
                                    :size="18"
                                    class="hover:text-primary-light text-slate-400"
                                />
                            </Link>
                            <Link
                                :href="edit(customer.id).url"
                                title="Edit customer"
                            >
                                <SquarePenIcon
                                    :size="18"
                                    class="hover:text-primary-light text-slate-400"
                                />
                            </Link>
                        </div>
                    </td>
                </tr>
            </template>

            <template #empty-state>
                <span class="font-medium text-gray-400">No customers yet.</span>
            </template>
        </DataTable>
    </div>
</template>
