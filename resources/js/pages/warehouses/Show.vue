<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { SquarePenIcon, TruckIcon } from '@lucide/vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import FactBox from '@/components/ui/FactBox.vue';
import FactBoxRow from '@/components/ui/FactBoxRow.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageNav from '@/components/ui/ObjectPageNav.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import DetailNav from '@/components/DetailNav.vue';
import { toHumanDate } from '@/composables/useDate';
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
    vans: Array<{
        id: number;
        code: string;
        vehicle_no: string | null;
        is_active: boolean;
        dsr: { id: number; name: string } | null;
    }>;
}

const props = defineProps<{
    warehouse: Warehouse;
    prev: number | null;
    next: number | null;
}>();

const sections = [{ id: 'vans', label: 'Vans' }];
</script>

<template>
    <div class="max-w-6xl space-y-6 py-4">
        <ObjectPageHeader
            :title="warehouse.name"
            :subtitle="warehouse.code"
            :back-href="index().url"
            :status="{
                label: warehouse.is_active ? 'Active' : 'Inactive',
                variant: warehouse.is_active ? 'success' : 'neutral',
            }"
        >
            <template #nav>
                <DetailNav
                    :prev-url="props.prev ? show(props.prev).url : null"
                    :next-url="props.next ? show(props.next).url : null"
                />
            </template>
            <template #actions>
                <Link :href="edit(warehouse.id).url">
                    <Button variant="ghost" class="flex items-center gap-2">
                        <SquarePenIcon :size="16" />
                        Edit
                    </Button>
                </Link>
            </template>
        </ObjectPageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[12rem_1fr_16rem]">
            <ObjectPageNav :sections="sections" />

            <div class="space-y-6">
                <ObjectPageSection id="vans" collapsible>
                    <template #header>
                        <h2
                            class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100"
                        >
                            <TruckIcon :size="16" />
                            Vans ({{ warehouse.vans.length }})
                        </h2>
                    </template>

                    <ul
                        v-if="warehouse.vans.length"
                        class="divide-y divide-slate-200 dark:divide-slate-700"
                    >
                        <li
                            v-for="van in warehouse.vans"
                            :key="van.id"
                            class="flex items-center justify-between py-2.5"
                        >
                            <div>
                                <p class="font-mono text-sm">{{ van.code }}</p>
                                <p
                                    class="text-xs text-slate-500 dark:text-slate-400"
                                >
                                    {{ van.dsr?.name ?? 'Unassigned' }}
                                </p>
                            </div>
                            <Badge
                                :variant="van.is_active ? 'success' : 'neutral'"
                                >{{
                                    van.is_active ? 'Active' : 'Inactive'
                                }}</Badge
                            >
                        </li>
                    </ul>
                    <p v-else class="text-sm text-slate-400 italic">
                        No vans assigned to this warehouse.
                    </p>
                </ObjectPageSection>
            </div>

            <FactBox title="Details">
                <FactBoxRow label="Location">{{
                    warehouse.location ?? '—'
                }}</FactBoxRow>
                <FactBoxRow label="Manager">{{
                    warehouse.manager?.name ?? '—'
                }}</FactBoxRow>
                <FactBoxRow label="Created">{{
                    toHumanDate(warehouse.created_at)
                }}</FactBoxRow>
                <FactBoxRow label="Updated">{{
                    toHumanDate(warehouse.updated_at)
                }}</FactBoxRow>
            </FactBox>
        </div>
    </div>
</template>
