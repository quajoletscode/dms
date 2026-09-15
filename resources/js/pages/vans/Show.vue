<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { HistoryIcon, SquarePenIcon } from '@lucide/vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import FactBox from '@/components/ui/FactBox.vue';
import FactBoxRow from '@/components/ui/FactBoxRow.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageNav from '@/components/ui/ObjectPageNav.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import DetailNav from '@/components/DetailNav.vue';
import { toHumanDate } from '@/composables/useDate';
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

const sections = [{ id: 'history', label: 'DSR Handover History' }];
</script>

<template>
    <div class="max-w-6xl space-y-6 py-4">
        <ObjectPageHeader
            :title="van.code"
            :subtitle="van.vehicle_no ?? 'No vehicle number'"
            :back-href="index().url"
            :status="{
                label: van.is_active ? 'Active' : 'Inactive',
                variant: van.is_active ? 'success' : 'neutral',
            }"
        >
            <template #nav>
                <DetailNav
                    :prev-url="props.prev ? show(props.prev).url : null"
                    :next-url="props.next ? show(props.next).url : null"
                />
            </template>
            <template #actions>
                <Link :href="edit(van.id).url">
                    <Button variant="ghost" class="flex items-center gap-2">
                        <SquarePenIcon :size="16" />
                        Reassign DSR
                    </Button>
                </Link>
            </template>
        </ObjectPageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[12rem_1fr_16rem]">
            <ObjectPageNav :sections="sections" />

            <div class="space-y-6">
                <ObjectPageSection id="history" collapsible>
                    <template #header>
                        <h2
                            class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100"
                        >
                            <HistoryIcon :size="16" />
                            DSR Handover History
                        </h2>
                    </template>

                    <ul
                        v-if="van.history.length"
                        class="divide-y divide-slate-200 dark:divide-slate-700"
                    >
                        <li
                            v-for="entry in van.history"
                            :key="entry.id"
                            class="py-2.5"
                        >
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium">
                                    {{ entry.dsr?.name ?? 'Unknown DSR' }}
                                </p>
                                <p
                                    class="text-xs text-slate-500 dark:text-slate-400"
                                >
                                    {{ toHumanDate(entry.assigned_at) }}
                                    <span v-if="entry.unassigned_at">
                                        –
                                        {{
                                            toHumanDate(entry.unassigned_at)
                                        }}</span
                                    >
                                    <span v-else> – present</span>
                                </p>
                            </div>
                            <p
                                v-if="entry.handover_note"
                                class="mt-1 text-xs text-slate-500 italic dark:text-slate-400"
                            >
                                {{ entry.handover_note }}
                            </p>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-slate-400 italic">
                        No handover history yet.
                    </p>
                </ObjectPageSection>
            </div>

            <FactBox title="Details">
                <FactBoxRow label="Warehouse">{{
                    van.warehouse.name
                }}</FactBoxRow>
                <FactBoxRow label="Current DSR">{{
                    van.dsr?.name ?? 'Unassigned'
                }}</FactBoxRow>
            </FactBox>
        </div>
    </div>
</template>
