<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, SquarePenIcon } from '@lucide/vue';
import DetailField from '@/components/DetailField.vue';
import DetailNav from '@/components/DetailNav.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import { edit, index, show } from '@/routes/suppliers';

interface Supplier {
    id: number;
    code: string;
    name: string;
    contact: string | null;
    payment_terms: string | null;
    is_active: boolean;
    opening_balance: string;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    supplier: Supplier;
    prev: number | null;
    next: number | null;
}>();
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link
                    :href="index().url"
                    class="hover:text-primary-light text-slate-400"
                    title="Back to suppliers"
                >
                    <ArrowLeftIcon :size="20" />
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <h1
                            class="text-2xl font-bold text-slate-900 dark:text-slate-100"
                        >
                            {{ supplier.name }}
                        </h1>
                        <Badge
                            :variant="
                                supplier.is_active ? 'success' : 'neutral'
                            "
                            >{{
                                supplier.is_active ? 'Active' : 'Inactive'
                            }}</Badge
                        >
                    </div>
                    <p
                        class="mt-1 font-mono text-sm text-slate-500 dark:text-slate-400"
                    >
                        {{ supplier.code }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <DetailNav
                    :prev-url="props.prev ? show(props.prev).url : null"
                    :next-url="props.next ? show(props.next).url : null"
                />
                <Link :href="edit(supplier.id).url">
                    <Button variant="ghost" class="flex items-center gap-2">
                        <SquarePenIcon :size="16" />
                        Edit
                    </Button>
                </Link>
            </div>
        </div>

        <Card class="max-w-2xl">
            <template #header>
                <h2
                    class="text-sm font-semibold text-slate-900 dark:text-slate-100"
                >
                    Details
                </h2>
            </template>

            <dl class="grid grid-cols-2 gap-x-4 gap-y-5">
                <DetailField label="Contact">{{
                    supplier.contact ?? '—'
                }}</DetailField>
                <DetailField label="Payment Terms">{{
                    supplier.payment_terms ?? '—'
                }}</DetailField>
                <DetailField label="Opening Balance (GHS)">{{
                    supplier.opening_balance
                }}</DetailField>
                <DetailField label="Created">{{
                    new Date(supplier.created_at).toLocaleDateString()
                }}</DetailField>
                <DetailField label="Updated">{{
                    new Date(supplier.updated_at).toLocaleDateString()
                }}</DetailField>
            </dl>
        </Card>
    </div>
</template>
