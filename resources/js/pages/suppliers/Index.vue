<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { EyeIcon, PlusIcon, SquarePenIcon } from '@lucide/vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import { create, edit, show } from '@/routes/suppliers';
import type { ColumnDef, SimplePaginationMeta } from '@/types';

interface Supplier {
    id: number;
    code: string;
    name: string;
    contact: string | null;
    is_active: boolean;
}

defineProps<{
    suppliers: Supplier[];
    meta?: SimplePaginationMeta;
}>();

const thead: ColumnDef[] = [
    { label: 'Code', key: 'code' },
    { label: 'Name', key: 'name' },
    'Contact',
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
                    Suppliers
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Suppliers and their payment terms.
                </p>
            </div>
            <Link :href="create().url">
                <Button class="flex items-center gap-2">
                    <PlusIcon :size="18" />
                    New Supplier
                </Button>
            </Link>
        </div>

        <DataTable
            :items="suppliers"
            :thead="thead"
            :is-loading="false"
            :meta="meta"
            :only="['suppliers', 'meta', 'request']"
            search-placeholder="Search suppliers..."
        >
            <template #default="{ items }">
                <tr
                    v-for="supplier in items"
                    :key="supplier.id"
                    class="border-b border-slate-200 dark:border-slate-700"
                >
                    <td class="px-3 py-2 font-mono text-sm">
                        {{ supplier.code }}
                    </td>
                    <td class="px-3 py-2">{{ supplier.name }}</td>
                    <td class="px-3 py-2 text-slate-500 dark:text-slate-400">
                        {{ supplier.contact ?? '—' }}
                    </td>
                    <td class="px-3 py-2">
                        <Badge
                            :variant="
                                supplier.is_active ? 'success' : 'neutral'
                            "
                            >{{
                                supplier.is_active ? 'Active' : 'Inactive'
                            }}</Badge
                        >
                    </td>
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-3">
                            <Link
                                :href="show(supplier.id).url"
                                title="View supplier"
                            >
                                <EyeIcon
                                    :size="18"
                                    class="hover:text-primary-light text-slate-400"
                                />
                            </Link>
                            <Link
                                :href="edit(supplier.id).url"
                                title="Edit supplier"
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
                <span class="font-medium text-gray-400">No suppliers yet.</span>
            </template>
        </DataTable>
    </div>
</template>
