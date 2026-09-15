<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { PlusIcon, SquarePenIcon } from '@lucide/vue';
import { computed } from 'vue';
import type { DriverVehicleProfile } from '@/components/CustomerDriverVehicleProfiles.vue';
import DetailField from '@/components/DetailField.vue';
import DetailNav from '@/components/DetailNav.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import FactBox from '@/components/ui/FactBox.vue';
import FactBoxRow from '@/components/ui/FactBoxRow.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageNav from '@/components/ui/ObjectPageNav.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { formatCurrency } from '@/composables/useApp';
import { toHumanDate } from '@/composables/useDate';
import { edit, index, show } from '@/routes/customers';

interface Customer {
    id: number;
    code: string;
    name: string;
    type: string;
    price_category: string | null;
    is_active: boolean;
    credit_limit: string;
    driver_vehicle_profiles: DriverVehicleProfile[];
    created_at: string;
    updated_at: string;
}

interface CustomerStatistics {
    balance: string;
    creditLimit: string;
    outstandingOrders: string;
    outstandingInvoices: string;
    overdueAmount: string;
    totalSales: string;
    totalPayments: string;
}

const props = defineProps<{
    customer: Customer;
    statistics: CustomerStatistics;
    prev: number | null;
    next: number | null;
}>();

const sections = [
    { id: 'details', label: 'Details' },
    { id: 'drivers', label: 'Drivers / Vehicles' },
];

const hasOverdueBalance = computed(
    () => Number(props.statistics.overdueAmount) > 0,
);
</script>

<template>
    <div class="max-w-6xl space-y-6 py-4">
        <ObjectPageHeader
            :title="customer.name"
            :subtitle="customer.code"
            :back-href="index().url"
            :status="{
                label: customer.is_active ? 'Active' : 'Inactive',
                variant: customer.is_active ? 'success' : 'neutral',
            }"
        >
            <template #nav>
                <DetailNav
                    :prev-url="props.prev ? show(props.prev).url : null"
                    :next-url="props.next ? show(props.next).url : null"
                />
            </template>
            <template #actions>
                <Link :href="edit(customer.id).url">
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
                <ObjectPageSection id="details" title="Details" collapsible>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-5">
                        <DetailField label="Type"
                            ><span class="capitalize">{{
                                customer.type
                            }}</span></DetailField
                        >
                        <DetailField label="Price Category">{{
                            customer.price_category ?? '—'
                        }}</DetailField>
                        <DetailField label="Credit Limit">{{
                            formatCurrency(Number(customer.credit_limit))
                        }}</DetailField>
                        <DetailField label="Created">{{
                            toHumanDate(customer.created_at)
                        }}</DetailField>
                        <DetailField label="Updated">{{
                            toHumanDate(customer.updated_at)
                        }}</DetailField>
                    </dl>
                </ObjectPageSection>

                <ObjectPageSection
                    id="drivers"
                    title="Drivers / Vehicles"
                    collapsible
                >
                    <template #header>
                        <div>
                            <h2
                                class="text-sm font-semibold text-slate-900 dark:text-slate-100"
                            >
                                Drivers / Vehicles
                            </h2>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                Debtor-specific list used when raising
                                receipts.
                            </p>
                        </div>
                    </template>

                    <div class="mb-4 flex justify-end">
                        <Link :href="edit(customer.id).url">
                            <Button
                                variant="ghost"
                                class="flex h-8 items-center gap-2 px-2 py-1 text-xs"
                            >
                                <PlusIcon :size="15" />
                                Add Line
                            </Button>
                        </Link>
                    </div>

                    <div
                        v-if="customer.driver_vehicle_profiles.length === 0"
                        class="py-6 text-sm text-slate-500 dark:text-slate-400"
                    >
                        No drivers or vehicles saved for this debtor.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr
                                    class="border-b border-slate-200 text-xs tracking-wide text-slate-500 uppercase dark:border-slate-700 dark:text-slate-400"
                                >
                                    <th class="px-3 py-2 font-medium">
                                        Driver
                                    </th>
                                    <th class="px-3 py-2 font-medium">
                                        Phone
                                    </th>
                                    <th class="px-3 py-2 font-medium">
                                        Vehicle
                                    </th>
                                    <th class="px-3 py-2 font-medium">
                                        Vehicle Description
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(
                                        profile, index
                                    ) in customer.driver_vehicle_profiles"
                                    :key="index"
                                    class="border-b border-slate-100 last:border-0 dark:border-slate-800"
                                >
                                    <td
                                        class="px-3 py-2 text-slate-700 dark:text-slate-300"
                                    >
                                        {{ profile.driver_name || '—' }}
                                    </td>
                                    <td
                                        class="px-3 py-2 text-slate-700 dark:text-slate-300"
                                    >
                                        {{ profile.driver_phone || '—' }}
                                    </td>
                                    <td
                                        class="px-3 py-2 text-slate-700 dark:text-slate-300"
                                    >
                                        {{ profile.vehicle_no || '—' }}
                                    </td>
                                    <td
                                        class="px-3 py-2 text-slate-700 dark:text-slate-300"
                                    >
                                        {{
                                            profile.vehicle_description || '—'
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </ObjectPageSection>
            </div>

            <FactBox title="Account Summary">
                <FactBoxRow label="Balance">{{
                    formatCurrency(Number(statistics.balance))
                }}</FactBoxRow>
                <FactBoxRow label="Credit Limit">{{
                    formatCurrency(Number(statistics.creditLimit))
                }}</FactBoxRow>
                <FactBoxRow label="Outstanding Orders">{{
                    formatCurrency(Number(statistics.outstandingOrders))
                }}</FactBoxRow>
                <FactBoxRow label="Outstanding Invoices">{{
                    formatCurrency(Number(statistics.outstandingInvoices))
                }}</FactBoxRow>
                <FactBoxRow label="Overdue Amount">
                    <span
                        :class="{
                            'font-semibold text-red-600 dark:text-red-400':
                                hasOverdueBalance,
                        }"
                        >{{
                            formatCurrency(Number(statistics.overdueAmount))
                        }}</span
                    >
                    <Badge
                        v-if="hasOverdueBalance"
                        variant="danger"
                        class="ml-2"
                        >Overdue</Badge
                    >
                </FactBoxRow>
                <FactBoxRow label="Total Sales">{{
                    formatCurrency(Number(statistics.totalSales))
                }}</FactBoxRow>
                <FactBoxRow label="Payments Received">{{
                    formatCurrency(Number(statistics.totalPayments))
                }}</FactBoxRow>
            </FactBox>
        </div>
    </div>
</template>
