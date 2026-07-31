<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { PlusIcon, SquarePenIcon } from '@lucide/vue';
import BaseTable from '@/components/DataTable/BaseTable.vue';
import Button from '@/components/ui/Button.vue';
import { create, edit } from '@/routes/suppliers';
import type { ColumnDef } from '@/types';

interface Supplier {
    id: number;
    code: string;
    name: string;
    contact: string | null;
    is_active: boolean;
}

defineProps<{
    suppliers: Supplier[];
}>();

const thead: ColumnDef[] = ['Code', 'Name', 'Contact', 'Status', 'Actions'];
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Suppliers</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Suppliers and their payment terms.</p>
            </div>
            <Link :href="create().url">
                <Button class="flex items-center gap-2">
                    <PlusIcon :size="18" />
                    New Supplier
                </Button>
            </Link>
        </div>

        <BaseTable :thead="thead">
            <tr v-for="supplier in suppliers" :key="supplier.id" class="border-b border-slate-200 dark:border-slate-700">
                <td class="px-3 py-2 font-mono text-sm">{{ supplier.code }}</td>
                <td class="px-3 py-2">{{ supplier.name }}</td>
                <td class="px-3 py-2 text-slate-500 dark:text-slate-400">{{ supplier.contact ?? '—' }}</td>
                <td class="px-3 py-2">
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="supplier.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                    >
                        {{ supplier.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-3 py-2">
                    <Link :href="edit(supplier.id).url" title="Edit supplier">
                        <SquarePenIcon :size="18" class="text-slate-400 hover:text-primary-light" />
                    </Link>
                </td>
            </tr>

            <template #empty>
                <span class="font-medium text-gray-400">No suppliers yet.</span>
            </template>
        </BaseTable>
    </div>
</template>
