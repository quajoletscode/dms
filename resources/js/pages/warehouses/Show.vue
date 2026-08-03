<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, SquarePenIcon, TruckIcon } from '@lucide/vue';
import DetailField from '@/components/DetailField.vue';
import DetailNav from '@/components/DetailNav.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import { edit, index, show } from '@/routes/warehouses';

interface Warehouse {
    id: number;
    code: string;
    name: string;
    location: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    manager: { id: number; name: string } | null;
    vans: Array<{ id: number; code: string; vehicle_no: string | null; is_active: boolean; dsr: { id: number; name: string } | null }>;
}

const props = defineProps<{
    warehouse: Warehouse;
    prev: number | null;
    next: number | null;
}>();
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link :href="index().url" class="text-slate-400 hover:text-primary-light" title="Back to warehouses">
                    <ArrowLeftIcon :size="20" />
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ warehouse.name }}</h1>
                        <Badge :variant="warehouse.is_active ? 'success' : 'neutral'">{{ warehouse.is_active ? 'Active' : 'Inactive' }}</Badge>
                    </div>
                    <p class="mt-1 font-mono text-sm text-slate-500 dark:text-slate-400">{{ warehouse.code }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <DetailNav :prev-url="props.prev ? show(props.prev).url : null" :next-url="props.next ? show(props.next).url : null" />
                <Link :href="edit(warehouse.id).url">
                    <Button variant="ghost" class="flex items-center gap-2">
                        <SquarePenIcon :size="16" />
                        Edit
                    </Button>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <Card>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Details</h2>
                </template>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-5">
                    <DetailField label="Location">{{ warehouse.location ?? '—' }}</DetailField>
                    <DetailField label="Manager">{{ warehouse.manager?.name ?? '—' }}</DetailField>
                    <DetailField label="Created">{{ new Date(warehouse.created_at).toLocaleDateString() }}</DetailField>
                    <DetailField label="Updated">{{ new Date(warehouse.updated_at).toLocaleDateString() }}</DetailField>
                </dl>
            </Card>

            <Card class="lg:col-span-2">
                <template #header>
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                        <TruckIcon :size="16" />
                        Vans ({{ warehouse.vans.length }})
                    </h2>
                </template>

                <ul v-if="warehouse.vans.length" class="divide-y divide-slate-200 dark:divide-slate-700">
                    <li v-for="van in warehouse.vans" :key="van.id" class="flex items-center justify-between py-2.5">
                        <div>
                            <p class="font-mono text-sm">{{ van.code }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ van.dsr?.name ?? 'Unassigned' }}</p>
                        </div>
                        <Badge :variant="van.is_active ? 'success' : 'neutral'">{{ van.is_active ? 'Active' : 'Inactive' }}</Badge>
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-400 italic">No vans assigned to this warehouse.</p>
            </Card>
        </div>
    </div>
</template>
