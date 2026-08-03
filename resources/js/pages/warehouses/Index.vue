<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { EyeIcon, PlusIcon, SquarePenIcon } from '@lucide/vue';
import BaseTable from '@/components/DataTable/BaseTable.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import { create, edit, show } from '@/routes/warehouses';
import type { ColumnDef } from '@/types';

interface Warehouse {
    id: number;
    code: string;
    name: string;
    location: string | null;
    is_active: boolean;
    manager: { id: number; name: string } | null;
}

defineProps<{
    warehouses: Warehouse[];
}>();

const thead: ColumnDef[] = ['Code', 'Name', 'Location', 'Manager', 'Status', 'Actions'];
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Warehouses</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Manage warehouses and their managers.</p>
            </div>
            <Link :href="create().url">
                <Button class="flex items-center gap-2">
                    <PlusIcon :size="18" />
                    New Warehouse
                </Button>
            </Link>
        </div>

        <BaseTable :thead="thead">
            <tr v-for="warehouse in warehouses" :key="warehouse.id" class="border-b border-slate-200 dark:border-slate-700">
                <td class="px-3 py-2 font-mono text-sm">{{ warehouse.code }}</td>
                <td class="px-3 py-2">{{ warehouse.name }}</td>
                <td class="px-3 py-2 text-slate-500 dark:text-slate-400">{{ warehouse.location ?? '—' }}</td>
                <td class="px-3 py-2">{{ warehouse.manager?.name ?? '—' }}</td>
                <td class="px-3 py-2">
                    <Badge :variant="warehouse.is_active ? 'success' : 'neutral'">{{ warehouse.is_active ? 'Active' : 'Inactive' }}</Badge>
                </td>
                <td class="px-3 py-2">
                    <div class="flex items-center gap-3">
                        <Link :href="show(warehouse.id).url" title="View warehouse">
                            <EyeIcon :size="18" class="text-slate-400 hover:text-primary-light" />
                        </Link>
                        <Link :href="edit(warehouse.id).url" title="Edit warehouse">
                            <SquarePenIcon :size="18" class="text-slate-400 hover:text-primary-light" />
                        </Link>
                    </div>
                </td>
            </tr>

            <template #empty>
                <span class="font-medium text-gray-400">No warehouses yet.</span>
            </template>
        </BaseTable>
    </div>
</template>
