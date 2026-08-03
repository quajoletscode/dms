<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, HistoryIcon, SquarePenIcon } from '@lucide/vue';
import DetailField from '@/components/DetailField.vue';
import DetailNav from '@/components/DetailNav.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import { edit, index, show } from '@/routes/vans';

interface VanDsrHistory {
    id: number;
    assigned_at: string;
    unassigned_at: string | null;
    handover_note: string | null;
    dsr: { id: number; name: string } | null;
}

interface Van {
    id: number;
    code: string;
    vehicle_no: string | null;
    is_active: boolean;
    warehouse: { id: number; name: string };
    dsr: { id: number; name: string } | null;
    history: VanDsrHistory[];
}

const props = defineProps<{
    van: Van;
    prev: number | null;
    next: number | null;
}>();
</script>

<template>
    <div class="space-y-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link :href="index().url" class="text-slate-400 hover:text-primary-light" title="Back to vans">
                    <ArrowLeftIcon :size="20" />
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ van.code }}</h1>
                        <Badge :variant="van.is_active ? 'success' : 'neutral'">{{ van.is_active ? 'Active' : 'Inactive' }}</Badge>
                    </div>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ van.vehicle_no ?? 'No vehicle number' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <DetailNav :prev-url="props.prev ? show(props.prev).url : null" :next-url="props.next ? show(props.next).url : null" />
                <Link :href="edit(van.id).url">
                    <Button variant="ghost" class="flex items-center gap-2">
                        <SquarePenIcon :size="16" />
                        Reassign DSR
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
                    <DetailField label="Warehouse">{{ van.warehouse.name }}</DetailField>
                    <DetailField label="Current DSR">{{ van.dsr?.name ?? 'Unassigned' }}</DetailField>
                </dl>
            </Card>

            <Card class="lg:col-span-2">
                <template #header>
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                        <HistoryIcon :size="16" />
                        DSR Handover History
                    </h2>
                </template>

                <ul v-if="van.history.length" class="divide-y divide-slate-200 dark:divide-slate-700">
                    <li v-for="entry in van.history" :key="entry.id" class="py-2.5">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium">{{ entry.dsr?.name ?? 'Unknown DSR' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ new Date(entry.assigned_at).toLocaleDateString() }}
                                <span v-if="entry.unassigned_at"> – {{ new Date(entry.unassigned_at).toLocaleDateString() }}</span>
                                <span v-else> – present</span>
                            </p>
                        </div>
                        <p v-if="entry.handover_note" class="mt-1 text-xs text-slate-500 italic dark:text-slate-400">{{ entry.handover_note }}</p>
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-400 italic">No handover history yet.</p>
            </Card>
        </div>
    </div>
</template>
