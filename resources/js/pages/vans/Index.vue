<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { EyeIcon, PlusIcon, SquarePenIcon } from '@lucide/vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import { create, edit, show } from '@/routes/vans';
import type { ColumnDef, SimplePaginationMeta } from '@/types';

interface Van {
    id: number;
    code: string;
    vehicle_no: string | null;
    is_active: boolean;
    warehouse: { id: number; name: string };
    dsr: { id: number; name: string } | null;
}

defineProps<{
    vans: Van[];
    meta?: SimplePaginationMeta;
}>();

const thead: ColumnDef[] = [
    { label: 'Code', key: 'code' },
    { label: 'Vehicle No.', key: 'vehicle_no' },
    'Warehouse',
    'DSR',
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
                    Van Storages
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Vans and their assigned DSRs.
                </p>
            </div>
            <Link :href="create().url">
                <Button class="flex items-center gap-2">
                    <PlusIcon :size="18" />
                    New Van
                </Button>
            </Link>
        </div>

        <DataTable
            :items="vans"
            :thead="thead"
            :is-loading="false"
            :meta="meta"
            :only="['vans', 'meta', 'request']"
            search-placeholder="Search vans..."
        >
            <template #default="{ items }">
                <tr
                    v-for="van in items"
                    :key="van.id"
                    class="border-b border-slate-200 dark:border-slate-700"
                >
                    <td class="px-3 py-2 font-mono text-sm">{{ van.code }}</td>
                    <td class="px-3 py-2 text-slate-500 dark:text-slate-400">
                        {{ van.vehicle_no ?? '—' }}
                    </td>
                    <td class="px-3 py-2">{{ van.warehouse.name }}</td>
                    <td class="px-3 py-2">{{ van.dsr?.name ?? '—' }}</td>
                    <td class="px-3 py-2">
                        <Badge
                            :variant="van.is_active ? 'success' : 'neutral'"
                            >{{ van.is_active ? 'Active' : 'Inactive' }}</Badge
                        >
                    </td>
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-3">
                            <Link :href="show(van.id).url" title="View van">
                                <EyeIcon
                                    :size="18"
                                    class="hover:text-primary-light text-slate-400"
                                />
                            </Link>
                            <Link
                                :href="edit(van.id).url"
                                title="Reassign DSR"
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
                <span class="font-medium text-gray-400">No vans yet.</span>
            </template>
        </DataTable>
    </div>
</template>
