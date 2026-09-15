<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { DoorClosedIcon, Loader2Icon, MinusIcon, PlusIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import Badge, { type Variant } from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import DocumentTotalRow from '@/components/ui/DocumentTotalRow.vue';
import DocumentTotals from '@/components/ui/DocumentTotals.vue';
import FactBox from '@/components/ui/FactBox.vue';
import FactBoxRow from '@/components/ui/FactBoxRow.vue';
import NumberInput from '@/components/ui/inputs/NumberInput.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageNav from '@/components/ui/ObjectPageNav.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { formatCurrency } from '@/composables/useApp';
import { formatFullDate } from '@/composables/useDate';
import { create as createPosSale } from '@/routes/pos';
import { cash, close as closeSession, index } from '@/routes/till-sessions';

interface TillSession {
    id: number;
    status: string;
    warehouse: { id: number; name: string };
    opening_float: string;
    closed_float: string | null;
    expected_float: string | null;
    variance: string | null;
    opened_at: string;
    closed_at: string | null;
    cash_movements: Array<{
        id: number;
        type: string;
        amount: string;
        reason: string;
    }>;
    invoice_payments: Array<{
        id: number;
        method: string;
        amount: string;
        invoice_no: string;
    }>;
}

const props = defineProps<{
    tillSession: TillSession;
}>();

const sections = [
    { id: 'summary', label: 'Summary' },
    { id: 'cash-movements', label: 'Cash Movements' },
    { id: 'payments', label: 'Payments' },
];

const statusVariant: Record<string, Variant> = {
    open: 'success',
    closed: 'neutral',
};

const varianceTone = computed<'default' | 'danger' | 'warning'>(() => {
    const variance = Number(props.tillSession.variance ?? 0);

    if (variance < 0) {
        return 'danger';
    }

    if (variance > 0) {
        return 'warning';
    }

    return 'default';
});

const activeCashForm = ref<'in' | 'out' | null>(null);

const cashForm = useForm({
    type: 'in' as 'in' | 'out',
    amount: '',
    reason: '',
});

function openCashForm(type: 'in' | 'out') {
    cashForm.reset();
    cashForm.type = type;
    activeCashForm.value = type;
}

function submitCashForm() {
    cashForm.post(cash(props.tillSession.id).url, {
        onSuccess: () => {
            activeCashForm.value = null;
        },
    });
}

const showCloseForm = ref(false);

const closeForm = useForm({
    counted_float: '',
});

function submitCloseForm() {
    closeForm.post(closeSession(props.tillSession.id).url);
}
</script>

<template>
    <div class="max-w-6xl space-y-6 py-4">
        <ObjectPageHeader
            :title="`Till Session — ${tillSession.warehouse.name}`"
            :subtitle="`Opened ${formatFullDate(new Date(tillSession.opened_at))}`"
            :back-href="index().url"
            :status="{
                label: tillSession.status,
                variant: statusVariant[tillSession.status] ?? 'neutral',
            }"
        >
            <template #actions>
                <template v-if="tillSession.status === 'open'">
                    <Link :href="createPosSale().url">
                        <Button variant="ghost">Ring Up Sale</Button>
                    </Link>
                    <Button
                        variant="light"
                        class="flex items-center gap-2"
                        @click="openCashForm('in')"
                    >
                        <PlusIcon :size="16" />
                        Cash In
                    </Button>
                    <Button
                        variant="light"
                        class="flex items-center gap-2"
                        @click="openCashForm('out')"
                    >
                        <MinusIcon :size="16" />
                        Cash Out
                    </Button>
                    <Button
                        variant="danger"
                        class="flex items-center gap-2"
                        @click="showCloseForm = !showCloseForm"
                    >
                        <DoorClosedIcon :size="16" />
                        Close Session
                    </Button>
                </template>
            </template>
        </ObjectPageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[12rem_1fr_16rem]">
            <ObjectPageNav :sections="sections" />

            <div class="space-y-6">
                <ObjectPageSection id="summary" title="Summary">
                    <DocumentTotals>
                        <DocumentTotalRow label="Opening Float">{{
                            formatCurrency(Number(tillSession.opening_float))
                        }}</DocumentTotalRow>
                        <DocumentTotalRow label="Expected Float">{{
                            tillSession.expected_float
                                ? formatCurrency(
                                      Number(tillSession.expected_float),
                                  )
                                : '—'
                        }}</DocumentTotalRow>
                        <DocumentTotalRow label="Counted Float">{{
                            tillSession.closed_float
                                ? formatCurrency(
                                      Number(tillSession.closed_float),
                                  )
                                : '—'
                        }}</DocumentTotalRow>
                        <DocumentTotalRow
                            label="Variance"
                            emphasized
                            :tone="varianceTone"
                            >{{
                                tillSession.variance
                                    ? formatCurrency(
                                          Number(tillSession.variance),
                                      )
                                    : '—'
                            }}</DocumentTotalRow
                        >
                    </DocumentTotals>

                    <form
                        v-if="showCloseForm && tillSession.status === 'open'"
                        class="mt-6 space-y-4 border-t border-slate-200 pt-4 dark:border-slate-700"
                        @submit.prevent="submitCloseForm"
                    >
                        <NumberInput
                            label="Counted Float"
                            required
                            v-model="closeForm.counted_float"
                            :error="closeForm.errors.counted_float"
                            :disabled="closeForm.processing"
                        />
                        <div class="flex justify-end gap-3">
                            <Button
                                variant="ghost"
                                type="button"
                                @click="showCloseForm = false"
                                >Cancel</Button
                            >
                            <Button
                                type="submit"
                                variant="danger"
                                class="flex items-center gap-2"
                                :disabled="closeForm.processing"
                            >
                                <Loader2Icon
                                    :size="18"
                                    class="animate-spin"
                                    v-if="closeForm.processing"
                                />
                                Confirm Close
                            </Button>
                        </div>
                    </form>
                </ObjectPageSection>

                <ObjectPageSection
                    id="cash-movements"
                    title="Cash Movements"
                    collapsible
                >
                    <form
                        v-if="activeCashForm && tillSession.status === 'open'"
                        class="mb-4 space-y-4 border-b border-slate-200 pb-4 dark:border-slate-700"
                        @submit.prevent="submitCashForm"
                    >
                        <p
                            class="text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            {{
                                activeCashForm === 'in'
                                    ? 'Record Cash In'
                                    : 'Record Cash Out'
                            }}
                        </p>
                        <NumberInput
                            label="Amount"
                            required
                            v-model="cashForm.amount"
                            :error="cashForm.errors.amount"
                            :disabled="cashForm.processing"
                        />
                        <TextInput
                            label="Reason"
                            required
                            v-model="cashForm.reason"
                            :error="cashForm.errors.reason"
                            :disabled="cashForm.processing"
                        />
                        <div class="flex justify-end gap-3">
                            <Button
                                variant="ghost"
                                type="button"
                                @click="activeCashForm = null"
                                >Cancel</Button
                            >
                            <Button
                                type="submit"
                                class="flex items-center gap-2"
                                :disabled="cashForm.processing"
                            >
                                <Loader2Icon
                                    :size="18"
                                    class="animate-spin"
                                    v-if="cashForm.processing"
                                />
                                Save
                            </Button>
                        </div>
                    </form>

                    <table
                        v-if="tillSession.cash_movements.length"
                        class="w-full text-sm"
                    >
                        <thead class="bg-slate-50 dark:bg-slate-900/60">
                            <tr
                                class="text-left text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400"
                            >
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2">Amount</th>
                                <th class="px-3 py-2">Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="movement in tillSession.cash_movements"
                                :key="movement.id"
                                class="border-t border-slate-100 dark:border-slate-800"
                            >
                                <td class="px-3 py-2 capitalize">
                                    {{ movement.type }}
                                </td>
                                <td class="px-3 py-2 tabular-nums">
                                    {{ movement.amount }}
                                </td>
                                <td class="px-3 py-2">{{ movement.reason }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p
                        v-else
                        class="text-sm text-slate-500 dark:text-slate-400"
                    >
                        No cash movements recorded.
                    </p>
                </ObjectPageSection>

                <ObjectPageSection id="payments" title="Payments" collapsible>
                    <table
                        v-if="tillSession.invoice_payments.length"
                        class="w-full text-sm"
                    >
                        <thead class="bg-slate-50 dark:bg-slate-900/60">
                            <tr
                                class="text-left text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400"
                            >
                                <th class="px-3 py-2">Invoice</th>
                                <th class="px-3 py-2">Method</th>
                                <th class="px-3 py-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="payment in tillSession.invoice_payments"
                                :key="payment.id"
                                class="border-t border-slate-100 dark:border-slate-800"
                            >
                                <td class="px-3 py-2 font-mono">
                                    {{ payment.invoice_no }}
                                </td>
                                <td class="px-3 py-2 capitalize">
                                    {{ payment.method.replace('_', ' ') }}
                                </td>
                                <td class="px-3 py-2 tabular-nums">
                                    {{ payment.amount }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p
                        v-else
                        class="text-sm text-slate-500 dark:text-slate-400"
                    >
                        No payments recorded yet.
                    </p>
                </ObjectPageSection>
            </div>

            <FactBox title="Details">
                <FactBoxRow label="Warehouse">{{
                    tillSession.warehouse.name
                }}</FactBoxRow>
                <FactBoxRow label="Opened At">{{
                    formatFullDate(new Date(tillSession.opened_at))
                }}</FactBoxRow>
                <FactBoxRow label="Status">
                    <Badge :variant="statusVariant[tillSession.status] ?? 'neutral'">{{
                        tillSession.status
                    }}</Badge>
                </FactBoxRow>
                <FactBoxRow label="Opening Float">{{
                    formatCurrency(Number(tillSession.opening_float))
                }}</FactBoxRow>
            </FactBox>
        </div>
    </div>
</template>
