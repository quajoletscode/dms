<script lang="ts" setup>
import { PlusIcon, Trash2Icon } from '@lucide/vue';
import NumberInput from '@/components/ui/inputs/NumberInput.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';

export type PaymentRow = { method: string; amount: string; reference: string };

withDefaults(
    defineProps<{
        errors?: Record<string, string>;
    }>(),
    { errors: () => ({}) },
);

const model = defineModel<PaymentRow[]>({ required: true });

const methodOptions = [
    { label: 'Cash', value: 'cash' },
    { label: 'Mobile Money', value: 'mobile_money' },
    { label: 'Card', value: 'card' },
    { label: 'Bank Transfer', value: 'bank_transfer' },
];

function addRow() {
    model.value.push({ method: 'cash', amount: '', reference: '' });
}

function removeRow(index: number) {
    model.value.splice(index, 1);
}
</script>

<template>
    <div class="space-y-3">
        <p
            v-if="model.length === 0"
            class="rounded-md border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
        >
            No payments added — the sale will be recorded fully on credit.
        </p>

        <div
            v-for="(row, index) in model"
            :key="index"
            class="grid grid-cols-1 gap-3 rounded-lg border border-slate-100 p-3 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-start sm:border-0 sm:p-0 dark:border-slate-800"
        >
            <SelectList
                label="Method"
                v-model="row.method"
                :options="methodOptions"
                :error="errors?.[`payments.${index}.method`]"
            />
            <NumberInput
                label="Amount"
                v-model="row.amount"
                :error="errors?.[`payments.${index}.amount`]"
            />
            <TextInput
                label="Reference"
                v-model="row.reference"
                :error="errors?.[`payments.${index}.reference`]"
            />
            <button
                type="button"
                class="mt-7 text-slate-300 transition-colors hover:text-red-500 sm:mt-7.5 dark:text-slate-600"
                title="Remove payment"
                @click="removeRow(index)"
            >
                <Trash2Icon :size="18" />
            </button>
        </div>

        <button
            type="button"
            class="hover:border-primary-light hover:text-primary-light inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-2.5 py-1.5 text-sm text-slate-600 transition-colors dark:border-slate-700 dark:text-slate-300"
            @click="addRow"
        >
            <PlusIcon :size="14" />
            Add payment
        </button>
    </div>
</template>
