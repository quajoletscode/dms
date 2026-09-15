<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { EyeIcon, PlusIcon } from '@lucide/vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import Badge, { type Variant } from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import { formatFullDate } from '@/composables/useDate';
import { create, show } from '@/routes/till-sessions';
import type { ColumnDef, SimplePaginationMeta } from '@/types';

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
    meta?: SimplePaginationMeta;
}>();

const thead: ColumnDef[] = [
    'Warehouse',
    { label: 'Status', key: 'status' },
    'Opening Float',
    { label: 'Opened At', key: 'opened_at' },
    'Actions',
];

const statusVariant: Record<string, Variant> = {
    open: 'success',
    closed: 'neutral',
};
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1
                    class="text-2xl font-bold text-slate-900 dark:text-slate-100"
                >
                    Till Sessions
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Your cash register sessions.
                </p>
            </div>
            <Link :href="create().url">
                <Button class="flex items-center gap-2">
                    <PlusIcon :size="18" />
                    Open Till Session
                </Button>
            </Link>
        </div>

        <DataTable
            :items="tillSessions"
            :thead="thead"
            :is-loading="false"
            :meta="meta"
            :only="['tillSessions', 'meta', 'request']"
            search-placeholder="Search till sessions..."
        >
            <template #default="{ items }">
                <tr
                    v-for="session in items"
                    :key="session.id"
                    class="border-b border-slate-200 dark:border-slate-700"
                >
                    <td class="px-3 py-2">{{ session.warehouse.name }}</td>
                    <td class="px-3 py-2">
                        <Badge
                            :variant="statusVariant[session.status] ?? 'neutral'"
                            >{{ session.status }}</Badge
                        >
                    </td>
                    <td class="px-3 py-2 tabular-nums">
                        {{ session.opening_float }}
                    </td>
                    <td class="px-3 py-2">
                        {{ formatFullDate(new Date(session.opened_at)) }}
                    </td>
                    <td class="px-3 py-2">
                        <Link
                            :href="show(session.id).url"
                            title="View till session"
                        >
                            <EyeIcon
                                :size="18"
                                class="hover:text-primary-light text-slate-400"
                            />
                        </Link>
                    </td>
                </tr>
            </template>

            <template #empty-state>
                <span class="font-medium text-gray-400"
                    >No till sessions yet.</span
                >
            </template>
        </DataTable>
    </div>
</template>
