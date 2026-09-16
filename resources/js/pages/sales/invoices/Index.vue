<script lang="ts" setup>
import { router, usePage } from '@inertiajs/vue3';
import { SearchIcon, XIcon } from '@lucide/vue';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import BaseTable from '@/components/DataTable/BaseTable.vue';
import SimplePagination from '@/components/DataTable/SimplePagination.vue';
import Badge, { type Variant } from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import { debounce, formatCurrency } from '@/composables/useApp';
import { useSort } from '@/composables/useSort';
import { toHumanDate } from '@/composables/useDate';
import { index, show } from '@/routes/invoices';
import type { ColumnDef, Request, SimplePaginationMeta } from '@/types';

type Status = 'unpaid' | 'partially_paid' | 'paid';
type StatusFilter = Status | 'overdue' | 'all';

interface InvoiceRow {
    id: number;
    no: string;
    source: string;
    status: Status;
    invoice_date: string | null;
    due_date: string | null;
    grand_total: string;
    balance: string;
    is_overdue: boolean;
    days_overdue: number | null;
    customer: { id: number; name: string; code: string };
    created_by: string | null;
}

interface Tile {
    amount: string;
    count: number;
}

const props = defineProps<{
    invoices: InvoiceRow[];
    statusFilter: StatusFilter;
    statusCounts: {
        all: number;
        unpaid: number;
        partially_paid: number;
        paid: number;
        overdue: number;
    };
    tiles: { outstanding: Tile; overdue: Tile; due_soon: Tile };
    filteredTotals: {
        document_count: number;
        total_value: string;
        total_outstanding: string;
    };
    meta?: SimplePaginationMeta;
}>();

const page = usePage();

const thead: ColumnDef[] = [
    { label: 'No.', key: 'no' },
    { label: 'Customer', key: 'customer' },
    { label: 'Document Date', key: 'invoice_date' },
    { label: 'Due Date', key: 'due_date' },
    'Created by',
    { label: 'Total', key: 'grand_total' },
    { label: 'Balance', key: 'balance' },
    { label: 'Status', key: 'status' },
];

const statusChips: { value: StatusFilter; label: string }[] = [
    { value: 'all', label: 'All' },
    { value: 'unpaid', label: 'Unpaid' },
    { value: 'partially_paid', label: 'Partially Paid' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'paid', label: 'Paid' },
];

const statusVariant: Record<Status, Variant> = {
    unpaid: 'warning',
    partially_paid: 'info',
    paid: 'success',
};

const { sort, direction, toggleSort } = useSort();

const getRequest = () =>
    (page.props.request as unknown as Request | undefined) ?? {
        q: null,
        per_page: null,
    };

const searchTerm = ref(getRequest().q ?? '');

function goTo(overrides: Record<string, unknown>) {
    const current = getRequest();

    router.get(
        index().url,
        { ...current, ...overrides, page: undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const debouncedSearch = debounce(
    (value: string) => goTo({ q: value || undefined }),
    500,
);

watch(searchTerm, (value) => debouncedSearch(value));

function setStatus(value: StatusFilter) {
    goTo({ status: value === 'all' ? undefined : value });
}

function clearFilters() {
    searchTerm.value = '';
    router.get(
        index().url,
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const hasActiveFilters = () =>
    !!getRequest().q || (props.statusFilter && props.statusFilter !== 'all');

// SI-LST-009: Up/Down moves a row cursor, Enter opens the document, and the
// cursor row is always scrolled into view. Ignored while typing in a field
// (search box) so arrow keys still work as expected there.
const cursor = ref<number | null>(null);
const rowRefs = ref<(HTMLElement | null)[]>([]);

watch(
    () => props.invoices,
    () => {
        rowRefs.value = [];
        cursor.value = props.invoices.length ? 0 : null;
    },
);

function scrollCursorIntoView() {
    nextTick(() => {
        if (cursor.value === null) {
            return;
        }

        rowRefs.value[cursor.value]?.scrollIntoView({ block: 'nearest' });
    });
}

function onKeydown(event: KeyboardEvent) {
    const target = event.target as HTMLElement | null;

    if (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) {
        return;
    }

    if (!props.invoices.length) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        cursor.value =
            cursor.value === null
                ? 0
                : Math.min(cursor.value + 1, props.invoices.length - 1);
        scrollCursorIntoView();
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        cursor.value =
            cursor.value === null ? 0 : Math.max(cursor.value - 1, 0);
        scrollCursorIntoView();
    } else if (event.key === 'Enter' && cursor.value !== null) {
        const row = props.invoices[cursor.value];

        if (row) {
            router.visit(show(row.id).url);
        }
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div class="space-y-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                Sales Invoices
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Posted customer invoices, their balances and ageing.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Card padding="sm">
                <p
                    class="text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400"
                >
                    Outstanding
                </p>
                <p
                    class="mt-1 text-xl font-semibold text-slate-900 tabular-nums dark:text-slate-100"
                >
                    {{ formatCurrency(Number(tiles.outstanding.amount)) }}
                </p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    {{ tiles.outstanding.count }} invoice{{
                        tiles.outstanding.count === 1 ? '' : 's'
                    }}
                </p>
            </Card>

            <Card
                padding="sm"
                class="cursor-pointer transition hover:border-red-300 dark:hover:border-red-700"
                @click="setStatus('overdue')"
            >
                <p
                    class="text-xs font-medium tracking-wide text-red-600 uppercase dark:text-red-400"
                >
                    Overdue
                </p>
                <p
                    class="mt-1 text-xl font-semibold text-red-700 tabular-nums dark:text-red-400"
                >
                    {{ formatCurrency(Number(tiles.overdue.amount)) }}
                </p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    {{ tiles.overdue.count }} invoice{{
                        tiles.overdue.count === 1 ? '' : 's'
                    }}
                </p>
            </Card>

            <Card padding="sm">
                <p
                    class="text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400"
                >
                    Due within 7 days
                </p>
                <p
                    class="mt-1 text-xl font-semibold text-slate-900 tabular-nums dark:text-slate-100"
                >
                    {{ formatCurrency(Number(tiles.due_soon.amount)) }}
                </p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    {{ tiles.due_soon.count }} invoice{{
                        tiles.due_soon.count === 1 ? '' : 's'
                    }}
                </p>
            </Card>
        </div>

        <div
            class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
        >
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-for="chip in statusChips"
                    :key="chip.value"
                    type="button"
                    class="rounded-full border px-3 py-1.5 text-sm font-medium transition"
                    :class="
                        statusFilter === chip.value
                            ? 'border-primary-light bg-primary-light/10 text-primary-light dark:border-primary-light dark:text-primary-light'
                            : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'
                    "
                    @click="setStatus(chip.value)"
                >
                    {{ chip.label }}
                    <span class="ml-1 text-xs opacity-70"
                        >({{ statusCounts[chip.value] }})</span
                    >
                </button>
            </div>

            <div class="group relative w-full sm:w-80">
                <TextInput
                    type="search"
                    class="pr-2 pl-10"
                    v-model="searchTerm"
                    size="md"
                    placeholder="Search no., customer, item..."
                >
                    <template #leading>
                        <div
                            class="absolute top-1/2 left-2.5 z-1 -translate-y-1/2"
                        >
                            <SearchIcon
                                :size="18"
                                class="group-focus-within:text-primary-light text-gray-400"
                            />
                        </div>
                    </template>
                </TextInput>
            </div>
        </div>

        <div
            class="relative overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700"
        >
            <BaseTable
                :thead="thead"
                :sort="sort"
                :direction="direction"
                @sort="toggleSort"
            >
                <tr
                    v-for="(invoice, rowIndex) in invoices"
                    :key="invoice.id"
                    :ref="(el) => (rowRefs[rowIndex] = el as HTMLElement)"
                    tabindex="0"
                    class="cursor-pointer border-b border-slate-100 text-sm transition last:border-b-0 dark:border-slate-800"
                    :class="[
                        cursor === rowIndex
                            ? 'bg-sky-50 dark:bg-sky-950/40'
                            : 'hover:bg-slate-50 dark:hover:bg-slate-800/60',
                        invoice.is_overdue ? 'border-l-2 border-l-red-400' : '',
                    ]"
                    @click="router.visit(show(invoice.id).url)"
                    @mouseenter="cursor = rowIndex"
                >
                    <td
                        class="px-3 py-2 font-mono font-medium text-slate-800 dark:text-slate-200"
                    >
                        {{ invoice.no }}
                    </td>
                    <td class="px-3 py-2">
                        <p class="text-slate-800 dark:text-slate-200">
                            {{ invoice.customer.name }}
                        </p>
                        <p class="font-mono text-xs text-slate-400">
                            {{ invoice.customer.code }}
                        </p>
                    </td>
                    <td
                        class="px-3 py-2 text-slate-600 tabular-nums dark:text-slate-300"
                    >
                        {{ toHumanDate(invoice.invoice_date) }}
                    </td>
                    <td
                        class="px-3 py-2 text-slate-600 tabular-nums dark:text-slate-300"
                    >
                        {{ toHumanDate(invoice.due_date) }}
                    </td>
                    <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                        {{ invoice.created_by ?? '—' }}
                    </td>
                    <td
                        class="px-3 py-2 font-medium text-slate-800 tabular-nums dark:text-slate-200"
                    >
                        {{ formatCurrency(Number(invoice.grand_total)) }}
                    </td>
                    <td class="px-3 py-2 tabular-nums">
                        <span
                            :class="
                                Number(invoice.balance) > 0
                                    ? 'text-slate-800 dark:text-slate-200'
                                    : 'text-slate-400'
                            "
                            >{{ formatCurrency(Number(invoice.balance)) }}</span
                        >
                        <p
                            v-if="invoice.is_overdue"
                            class="text-xs font-medium text-red-600 dark:text-red-400"
                        >
                            {{ invoice.days_overdue }}d overdue
                        </p>
                    </td>
                    <td class="px-3 py-2">
                        <Badge
                            :variant="
                                invoice.is_overdue
                                    ? 'danger'
                                    : (statusVariant[invoice.status] ??
                                      'neutral')
                            "
                        >
                            {{
                                invoice.is_overdue
                                    ? 'Overdue'
                                    : invoice.status.replace('_', ' ')
                            }}
                        </Badge>
                    </td>
                </tr>

                <template #empty v-if="invoices.length === 0">
                    <div
                        class="flex flex-col items-center gap-3 py-12 text-center"
                    >
                        <p
                            class="font-medium text-slate-500 dark:text-slate-400"
                        >
                            {{
                                hasActiveFilters()
                                    ? 'No invoices match the current search and filters.'
                                    : 'No invoices yet.'
                            }}
                        </p>
                        <button
                            v-if="hasActiveFilters()"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            @click="clearFilters"
                        >
                            <XIcon :size="14" />
                            Clear filters
                        </button>
                    </div>
                </template>
            </BaseTable>
        </div>

        <div
            class="flex flex-col gap-2 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between dark:text-slate-400"
        >
            <p>
                {{ filteredTotals.document_count }} document{{
                    filteredTotals.document_count === 1 ? '' : 's'
                }}
                · Total
                <span class="font-medium text-slate-700 dark:text-slate-200">{{
                    formatCurrency(Number(filteredTotals.total_value))
                }}</span>
                · Outstanding
                <span class="font-medium text-slate-700 dark:text-slate-200">{{
                    formatCurrency(Number(filteredTotals.total_outstanding))
                }}</span>
            </p>
        </div>

        <SimplePagination :meta="props.meta" :route-url="index().url" />
    </div>
</template>
