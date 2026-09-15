<script lang="ts" setup>
import { PlusIcon, Trash2Icon } from '@lucide/vue';
import { computed, watch } from 'vue';
import NumberInput from '@/components/ui/inputs/NumberInput.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';

export interface LineItemColumn {
    /** Row object key this column reads/writes, e.g. "qty_ordered". */
    key: string;
    label: string;
    type?: 'number' | 'text';
    helperText?: string;
}

export interface ProductOption {
    id: number;
    /** Text shown in the product picker, e.g. "SKU-100 — Diesel (20L)". */
    label: string;
    /** Prefilled into a numeric column (matched by column key) when the row's product changes, e.g. { unit_cost: '12.50' }. */
    defaults?: Record<string, string>;
}

/**
 * A blank spreadsheet-style grid for picking many existing products into a
 * single form submission. Deliberately entity-agnostic — the caller decides
 * which extra numeric/text columns exist (qty, unit cost, discount, ...) via
 * `columns`, so the same grid works for a Purchase Order today and any future
 * multi-product document (stock transfer, price list, ...) without changes here.
 */
const props = withDefaults(
    defineProps<{
        products: ProductOption[];
        columns: LineItemColumn[];
        minRows?: number;
        bulkAddSize?: number;
        errors?: Record<string, string>;
    }>(),
    {
        minRows: 12,
        bulkAddSize: 10,
        errors: () => ({}),
    },
);

export type LineItemRow = { product_id: string | number } & Record<
    string,
    string | number
>;
type Row = LineItemRow;

const model = defineModel<Row[]>({ required: true });

function blankRow(): Row {
    const row = { product_id: '' } as Row;

    for (const column of props.columns) {
        row[column.key] = '';
    }

    return row;
}

function ensureMinimumRows() {
    while (model.value.length < props.minRows) {
        model.value.push(blankRow());
    }
}

ensureMinimumRows();

const productOptions = computed(() =>
    props.products.map((product) => ({
        label: product.label,
        value: product.id,
    })),
);

const filledCount = computed(
    () =>
        model.value.filter(
            (row) => row.product_id !== '' && row.product_id !== null,
        ).length,
);

function onProductSelected(
    row: Row,
    productId: string | number | Record<string, unknown>,
) {
    if (typeof productId === 'object') {
        return;
    }

    row.product_id = productId;

    const product = props.products.find((p) => p.id === Number(productId));

    if (product?.defaults) {
        for (const [key, value] of Object.entries(product.defaults)) {
            if (row[key] === '' || row[key] === undefined) {
                row[key] = value;
            }
        }
    }

    // Grow the grid the moment someone fills the last row — the list never
    // visibly "runs out", so bulk entry never has to stop to add more rows.
    const isLastRow = model.value[model.value.length - 1] === row;

    if (isLastRow) {
        model.value.push(blankRow());
    }
}

function addRows(count: number) {
    for (let i = 0; i < count; i++) {
        model.value.push(blankRow());
    }
}

function removeRow(index: number) {
    model.value.splice(index, 1);
    ensureMinimumRows();
}

function errorFor(index: number, key: string): string | undefined {
    return props.errors?.[`items.${index}.${key}`];
}

watch(() => props.minRows, ensureMinimumRows);
</script>

<template>
    <div class="space-y-3">
        <div
            class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700"
        >
            <table class="w-full border-collapse text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/60">
                    <tr
                        class="text-left text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400"
                    >
                        <th class="w-10 px-3 py-2">#</th>
                        <th class="min-w-64 px-3 py-2">Product</th>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="w-36 px-3 py-2"
                        >
                            {{ column.label }}
                        </th>
                        <th class="w-10 px-3 py-2" />
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, index) in model"
                        :key="index"
                        class="border-t border-slate-100 transition-colors hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900/40"
                    >
                        <td
                            class="px-3 py-2 align-top text-xs text-slate-400 tabular-nums"
                        >
                            {{ index + 1 }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            <SelectList
                                :model-value="row.product_id"
                                @update:model-value="
                                    (value) => onProductSelected(row, value)
                                "
                                :options="productOptions"
                                placeholder="Select a product…"
                                :error="errorFor(index, 'product_id')"
                            />
                        </td>
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="px-3 py-2 align-top"
                        >
                            <NumberInput
                                v-if="column.type !== 'text'"
                                v-model="row[column.key]"
                                :error="errorFor(index, column.key)"
                                :helper-text="column.helperText"
                            />
                            <input
                                v-else
                                v-model="row[column.key]"
                                type="text"
                                class="hover:outline-primary-light focus:outline-primary-light w-full rounded-md px-1.25 py-2 outline-[1.5px] dark:bg-inherit"
                            />
                        </td>
                        <td class="px-3 py-2 text-right align-top">
                            <button
                                type="button"
                                class="text-slate-300 transition-colors hover:text-red-500 dark:text-slate-600"
                                title="Remove row"
                                @click="removeRow(index)"
                            >
                                <Trash2Icon :size="16" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between text-sm">
            <p class="text-slate-500 dark:text-slate-400">
                {{ filledCount }} of {{ model.length }} rows filled — blank rows
                are ignored on submit.
            </p>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="hover:border-primary-light hover:text-primary-light inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-2.5 py-1.5 text-slate-600 transition-colors dark:border-slate-700 dark:text-slate-300"
                    @click="addRows(1)"
                >
                    <PlusIcon :size="14" />
                    Add row
                </button>
                <button
                    type="button"
                    class="hover:border-primary-light hover:text-primary-light inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-2.5 py-1.5 text-slate-600 transition-colors dark:border-slate-700 dark:text-slate-300"
                    @click="addRows(bulkAddSize)"
                >
                    <PlusIcon :size="14" />
                    Add {{ bulkAddSize }} rows
                </button>
            </div>
        </div>
    </div>
</template>
