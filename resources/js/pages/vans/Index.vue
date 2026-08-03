<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { EyeIcon, PlusIcon, SquarePenIcon } from '@lucide/vue';
import BaseTable from '@/components/DataTable/BaseTable.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import { create, edit, show } from '@/routes/vans';
import type { ColumnDef } from '@/types';

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
}>();

const thead: ColumnDef[] = ['Code', 'Vehicle No.', 'Warehouse', 'DSR', 'Status', 'Actions'];
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Van Storages</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Vans and their assigned DSRs.</p>
            </div>
            <Link :href="create().url">
                <Button class="flex items-center gap-2">
                    <PlusIcon :size="18" />
                    New Van
                </Button>
            </Link>
        </div>

        <BaseTable :thead="thead">
            <tr v-for="van in vans" :key="van.id" class="border-b border-slate-200 dark:border-slate-700">
                <td class="px-3 py-2 font-mono text-sm">{{ van.code }}</td>
                <td class="px-3 py-2 text-slate-500 dark:text-slate-400">{{ van.vehicle_no ?? '—' }}</td>
                <td class="px-3 py-2">{{ van.warehouse.name }}</td>
                <td class="px-3 py-2">{{ van.dsr?.name ?? '—' }}</td>
                <td class="px-3 py-2">
                    <Badge :variant="van.is_active ? 'success' : 'neutral'">{{ van.is_active ? 'Active' : 'Inactive' }}</Badge>
                </td>
                <td class="px-3 py-2">
                    <div class="flex items-center gap-3">
                        <Link :href="show(van.id).url" title="View van">
                            <EyeIcon :size="18" class="text-slate-400 hover:text-primary-light" />
                        </Link>
                        <Link :href="edit(van.id).url" title="Reassign DSR">
                            <SquarePenIcon :size="18" class="text-slate-400 hover:text-primary-light" />
                        </Link>
                    </div>
                </td>
            </tr>

            <template #empty>
                <span class="font-medium text-gray-400">No vans yet.</span>
            </template>
        </BaseTable>
    </div>
</template>
