<script lang="ts" setup>
import { PlusIcon, Trash2Icon } from '@lucide/vue';
import { computed, watch } from 'vue';
import NumberInput from '@/components/ui/inputs/NumberInput.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';

export interface DebtorBillRow {
    product_id: string | number;
    vehicle_no: string;
    line_description: string;
    qty: string | number;
    unit_price: string | number;
    discount: string | number;
}

export interface DebtorVehicleOption {
    vehicle_no: string | null;
}

export interface ProductOption {
    id: number;
    label: string;
    defaults?: Record<string, string>;
}

const props = withDefaults(
    defineProps<{
        saleDate: string;
        products: ProductOption[];
        vehicles?: DebtorVehicleOption[];
        minRows?: number;
        bulkAddSize?: number;
        errors?: Record<string, string>;
    }>(),
    {
        vehicles: () => [],
        minRows: 37,
        bulkAddSize: 10,
        errors: () => ({}),
    },
);

const model = defineModel<DebtorBillRow[]>({ required: true });

const blankRow = (): DebtorBillRow => ({
    product_id: '',
    vehicle_no: '',
    line_description: '',
    qty: '',
    unit_price: '',
    discount: '0',
});

const ensureMinimumRows = () => {
    while (model.value.length < props.minRows) {
        model.value.push(blankRow());
    }
};

ensureMinimumRows();

const productOptions = computed(() =>
    props.products.map((product) => ({
        label: product.label,
        value: product.id,
    })),
);

const vehicleOptions = computed(() =>
    props.vehicles
        .map((vehicle) => vehicle.vehicle_no)
        .filter((vehicleNo): vehicleNo is string => Boolean(vehicleNo))
        .map((vehicleNo) => ({
            label: vehicleNo,
            value: vehicleNo,
        })),
);

const formatAmount = (amount: number) =>
    amount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const lineAmount = (row: DebtorBillRow) => {
    const qty = Number(row.qty) || 0;
    const unitPrice = Number(row.unit_price) || 0;
    const discount = Number(row.discount) || 0;

    return Math.max(0, qty * unitPrice - discount);
};

const filledRows = computed(() =>
    model.value.filter((row) => row.product_id !== '' && row.qty !== ''),
);

const billTotal = computed(() =>
    formatAmount(
        filledRows.value.reduce((sum, row) => sum + lineAmount(row), 0),
    ),
);

const onProductSelected = (
    row: DebtorBillRow,
    productId: string | number | Record<string, unknown>,
) => {
    if (typeof productId === 'object') {
        return;
    }

    row.product_id = productId;

    const product = props.products.find(
        (item) => item.id === Number(productId),
    );

    if (product?.defaults) {
        for (const [key, value] of Object.entries(product.defaults)) {
            if (row[key as keyof DebtorBillRow] === '') {
                row[key as keyof DebtorBillRow] = value;
            }
        }
    }

    if (!row.line_description) {
        row.line_description = product?.label ?? '';
    }

    if (model.value[model.value.length - 1] === row) {
        model.value.push(blankRow());
    }
};

const addRows = (count: number) => {
    for (let index = 0; index < count; index++) {
        model.value.push(blankRow());
    }
};

const removeRow = (index: number) => {
    model.value.splice(index, 1);
    ensureMinimumRows();
};

const errorFor = (index: number, key: string): string | undefined => {
    return props.errors?.[`items.${index}.${key}`];
};

watch(() => props.minRows, ensureMinimumRows);

defineExpose({ billTotal, filledRows });
</script>

<template>
    <div class="space-y-3">
        <div
            class="overflow-x-auto rounded-sm border border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-900"
        >
            <table class="w-full border-collapse text-xs">
                <thead>
                    <tr
                        class="border-b border-slate-900 bg-sky-600 text-left font-semibold text-white dark:border-slate-600"
                    >
                        <th class="w-10 px-2 py-1 text-center">No.</th>
                        <th class="w-28 px-2 py-1">Date</th>
                        <th class="min-w-36 px-2 py-1">Vehicle</th>
                        <th class="min-w-52 px-2 py-1">Description</th>
                        <th class="w-28 px-2 py-1 text-right">QTY</th>
                        <th class="w-32 px-2 py-1 text-right">Prices</th>
                        <th class="w-36 px-2 py-1 text-right">Amount</th>
                        <th class="w-10 px-2 py-1"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, index) in model"
                        :key="index"
                        class="border-b border-white odd:bg-sky-50 even:bg-white dark:border-slate-800 dark:odd:bg-slate-800/70 dark:even:bg-slate-900"
                    >
                        <td class="px-2 py-1 text-center tabular-nums">
                            {{ index + 1 }}
                        </td>
                        <td class="px-2 py-1 font-mono tabular-nums">
                            {{ saleDate }}
                        </td>
                        <td class="px-2 py-1">
                            <SelectList
                                v-if="vehicleOptions.length"
                                :model-value="row.vehicle_no"
                                @update:model-value="
                                    (value) => {
                                        row.vehicle_no =
                                            typeof value === 'object'
                                                ? ''
                                                : String(value);
                                    }
                                "
                                :options="vehicleOptions"
                                placeholder="Vehicle"
                                :error="errorFor(index, 'vehicle_no')"
                            />
                            <TextInput
                                v-else
                                v-model="row.vehicle_no"
                                placeholder="Vehicle"
                                :error="errorFor(index, 'vehicle_no')"
                            />
                        </td>
                        <td class="px-2 py-1">
                            <SelectList
                                :model-value="row.product_id"
                                @update:model-value="
                                    (value) => onProductSelected(row, value)
                                "
                                :options="productOptions"
                                placeholder="Select item"
                                :error="errorFor(index, 'product_id')"
                            />
                        </td>
                        <td class="px-2 py-1">
                            <NumberInput
                                v-model="row.qty"
                                :error="errorFor(index, 'qty')"
                                class="text-right"
                            />
                        </td>
                        <td class="px-2 py-1">
                            <NumberInput
                                v-model="row.unit_price"
                                :error="errorFor(index, 'unit_price')"
                                class="text-right"
                            />
                        </td>
                        <td
                            class="px-2 py-1 text-right font-medium tabular-nums"
                        >
                            {{
                                lineAmount(row) > 0
                                    ? formatAmount(lineAmount(row))
                                    : '-'
                            }}
                        </td>
                        <td class="px-2 py-1 text-center">
                            <button
                                type="button"
                                class="text-slate-400 transition hover:text-red-500"
                                title="Remove row"
                                @click="removeRow(index)"
                            >
                                <Trash2Icon :size="14" />
                            </button>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr
                        class="border-t-2 border-orange-500 bg-white text-slate-900 dark:bg-slate-950 dark:text-slate-100"
                    >
                        <td colspan="5"></td>
                        <td class="px-2 py-1 text-right font-semibold">Bill</td>
                        <td class="px-2 py-1 text-right font-bold tabular-nums">
                            {{ billTotal }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex items-center justify-between gap-3 text-sm">
            <p class="text-slate-500 dark:text-slate-400">
                {{ filledRows.length }} rows will be posted to this debtor.
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
