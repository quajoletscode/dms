<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { EyeIcon, PlusIcon } from '@lucide/vue';
import BaseTable from '@/components/DataTable/BaseTable.vue';
import Badge, { type Variant } from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import { create, show } from '@/routes/till-sessions';
import type { ColumnDef } from '@/types';

interface TillSessionRow {
    id: number;
    status: string;
    warehouse: { id: number; name: string };
    opening_float: string;
    opened_at: string;
    closed_at: string | null;
}

defineProps<{
    tillSessions: TillSessionRow[];
}>();

const thead: ColumnDef[] = ['Warehouse', 'Status', 'Opening Float', 'Opened At', 'Actions'];

const statusVariant: Record<string, Variant> = {
    open: 'success',
    closed: 'neutral',
};
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Till Sessions</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Your cash register sessions.</p>
            </div>
            <Link :href="create().url">
                <Button class="flex items-center gap-2">
                    <PlusIcon :size="18" />
                    Open Till Session
                </Button>
            </Link>
        </div>

        <BaseTable :thead="thead">
            <tr v-for="session in tillSessions" :key="session.id" class="border-b border-slate-200 dark:border-slate-700">
                <td class="px-3 py-2">{{ session.warehouse.name }}</td>
                <td class="px-3 py-2">
                    <Badge :variant="statusVariant[session.status] ?? 'neutral'">{{ session.status }}</Badge>
                </td>
                <td class="px-3 py-2 tabular-nums">{{ session.opening_float }}</td>
                <td class="px-3 py-2">{{ new Date(session.opened_at).toLocaleString() }}</td>
                <td class="px-3 py-2">
                    <Link :href="show(session.id).url" title="View till session">
                        <EyeIcon :size="18" class="text-slate-400 hover:text-primary-light" />
                    </Link>
                </td>
            </tr>

            <template #empty>
                <span class="font-medium text-gray-400">No till sessions yet.</span>
            </template>
        </BaseTable>
    </div>
</template>
