<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, SquarePenIcon } from '@lucide/vue';
import DetailField from '@/components/DetailField.vue';
import DetailNav from '@/components/DetailNav.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import { edit, index, show } from '@/routes/customers';

interface Customer {
    id: number;
    code: string;
    name: string;
    type: string;
    price_category: string | null;
    is_active: boolean;
    credit_limit: string;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    customer: Customer;
    prev: number | null;
    next: number | null;
}>();
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link :href="index().url" class="text-slate-400 hover:text-primary-light" title="Back to customers">
                    <ArrowLeftIcon :size="20" />
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ customer.name }}</h1>
                        <Badge :variant="customer.is_active ? 'success' : 'neutral'">{{ customer.is_active ? 'Active' : 'Inactive' }}</Badge>
                    </div>
                    <p class="mt-1 font-mono text-sm text-slate-500 dark:text-slate-400">{{ customer.code }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <DetailNav :prev-url="props.prev ? show(props.prev).url : null" :next-url="props.next ? show(props.next).url : null" />
                <Link :href="edit(customer.id).url">
                    <Button variant="ghost" class="flex items-center gap-2">
                        <SquarePenIcon :size="16" />
                        Edit
                    </Button>
                </Link>
            </div>
        </div>

        <Card class="max-w-2xl">
            <template #header>
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Details</h2>
            </template>

            <dl class="grid grid-cols-2 gap-x-4 gap-y-5">
                <DetailField label="Type"><span class="capitalize">{{ customer.type }}</span></DetailField>
                <DetailField label="Price Category">{{ customer.price_category ?? '—' }}</DetailField>
                <DetailField label="Credit Limit (GHS)">{{ customer.credit_limit }}</DetailField>
                <DetailField label="Created">{{ new Date(customer.created_at).toLocaleDateString() }}</DetailField>
                <DetailField label="Updated">{{ new Date(customer.updated_at).toLocaleDateString() }}</DetailField>
            </dl>
        </Card>
    </div>
</template>
