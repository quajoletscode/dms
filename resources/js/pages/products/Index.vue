<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { EyeIcon, PlusIcon, SquarePenIcon } from '@lucide/vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import { create, edit, show } from '@/routes/products';
import type { ColumnDef, SimplePaginationMeta } from '@/types';

interface Product {
    id: number;
    sku: string;
    name: string;
    is_active: boolean;
    category: { id: number; name: string } | null;
    unit: { id: number; name: string };
}

defineProps<{
    products: Product[];
    meta?: SimplePaginationMeta;
}>();

const thead: ColumnDef[] = [
    { label: 'SKU', key: 'sku' },
    { label: 'Name', key: 'name' },
    'Category',
    'Unit',
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
                    Products
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    The central product catalogue.
                </p>
            </div>
            <Link :href="create().url">
                <Button class="flex items-center gap-2">
                    <PlusIcon :size="18" />
                    New Product
                </Button>
            </Link>
        </div>

        <DataTable
            :items="products"
            :thead="thead"
            :is-loading="false"
            :meta="meta"
            :only="['products', 'meta', 'request']"
            search-placeholder="Search products..."
        >
            <template #default="{ items }">
                <tr
                    v-for="product in items"
                    :key="product.id"
                    class="border-b border-slate-200 dark:border-slate-700"
                >
                    <td class="px-3 py-2 font-mono text-sm">
                        {{ product.sku }}
                    </td>
                    <td class="px-3 py-2">{{ product.name }}</td>
                    <td class="px-3 py-2 text-slate-500 dark:text-slate-400">
                        {{ product.category?.name ?? '—' }}
                    </td>
                    <td class="px-3 py-2">{{ product.unit.name }}</td>
                    <td class="px-3 py-2">
                        <Badge
                            :variant="
                                product.is_active ? 'success' : 'neutral'
                            "
                            >{{
                                product.is_active ? 'Active' : 'Inactive'
                            }}</Badge
                        >
                    </td>
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-3">
                            <Link
                                :href="show(product.id).url"
                                title="View product"
                            >
                                <EyeIcon
                                    :size="18"
                                    class="hover:text-primary-light text-slate-400"
                                />
                            </Link>
                            <Link
                                :href="edit(product.id).url"
                                title="Edit product"
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
                <span class="font-medium text-gray-400">No products yet.</span>
            </template>
        </DataTable>
    </div>
</template>
