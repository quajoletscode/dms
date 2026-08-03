<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, SquarePenIcon } from '@lucide/vue';
import DetailField from '@/components/DetailField.vue';
import DetailNav from '@/components/DetailNav.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import { edit, index, show } from '@/routes/products';

interface Product {
    id: number;
    sku: string;
    barcode: string | null;
    name: string;
    tax_rate: string | number;
    track_expiry: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    category: { id: number; name: string } | null;
    unit: { id: number; name: string };
    cost_price: string;
    wholesale_price: string;
    retail_price: string;
    van_price: string;
    reorder_level: string;
}

const props = defineProps<{
    product: Product;
    prev: number | null;
    next: number | null;
}>();
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link :href="index().url" class="text-slate-400 hover:text-primary-light" title="Back to products">
                    <ArrowLeftIcon :size="20" />
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ product.name }}</h1>
                        <Badge :variant="product.is_active ? 'success' : 'neutral'">{{ product.is_active ? 'Active' : 'Inactive' }}</Badge>
                    </div>
                    <p class="mt-1 font-mono text-sm text-slate-500 dark:text-slate-400">{{ product.sku }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <DetailNav :prev-url="props.prev ? show(props.prev).url : null" :next-url="props.next ? show(props.next).url : null" />
                <Link :href="edit(product.id).url">
                    <Button variant="ghost" class="flex items-center gap-2">
                        <SquarePenIcon :size="16" />
                        Edit
                    </Button>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Details</h2>
                </template>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-3">
                    <DetailField label="Barcode">{{ product.barcode ?? '—' }}</DetailField>
                    <DetailField label="Category">{{ product.category?.name ?? '—' }}</DetailField>
                    <DetailField label="Unit">{{ product.unit.name }}</DetailField>
                    <DetailField label="Tax Rate">{{ product.tax_rate }}%</DetailField>
                    <DetailField label="Reorder Level">{{ product.reorder_level }}</DetailField>
                    <DetailField label="Tracks Expiry">{{ product.track_expiry ? 'Yes' : 'No' }}</DetailField>
                    <DetailField label="Created">{{ new Date(product.created_at).toLocaleDateString() }}</DetailField>
                    <DetailField label="Updated">{{ new Date(product.updated_at).toLocaleDateString() }}</DetailField>
                </dl>
            </Card>

            <Card>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Pricing (GHS)</h2>
                </template>

                <dl class="space-y-4">
                    <DetailField label="Cost Price">{{ product.cost_price }}</DetailField>
                    <DetailField label="Wholesale Price">{{ product.wholesale_price }}</DetailField>
                    <DetailField label="Retail Price">{{ product.retail_price }}</DetailField>
                    <DetailField label="Van Price">{{ product.van_price }}</DetailField>
                </dl>
            </Card>
        </div>
    </div>
</template>
