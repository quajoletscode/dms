<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { FileTextIcon, PlusIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import { show } from '@/routes/invoices';
import type { SimplePaginationMeta } from '@/types';

interface InvoiceRow {
    id: number;
    no: string;
    source: string;
    status: string;
    grand_total: string;
    customer: { id: number; name: string };
    invoice_date?: string | null;
    due_date?: string | null;
}

const props = defineProps<{
    invoices: InvoiceRow[];
    meta?: SimplePaginationMeta;
}>();

const primaryTabs = ['Finance', 'Cash Management', 'Sales', 'Purchasing', 'Shopify', 'All Reports'];
const listActions = ['New', 'Edit', 'Delete', 'Post', 'Release', 'Request Approval', 'Print', 'More options'];
const selection = ref(0);

const selectedInvoice = computed(() => {
    if (!props.invoices?.length) {
        return null;
    }

    return props.invoices[Math.min(selection.value, props.invoices.length - 1)] ?? null;
});

const formatDate = (value?: string | null) => {
    if (!value) {
        return '—';
    }

    return value;
};

const invoiceRows = computed(() => props.invoices ?? []);
</script>

<template>
    <div class="min-h-screen bg-[#edf1f5] text-[#262b3c]">
        <div class="border-b border-slate-200 bg-white">
            <div class="flex h-16 items-center justify-between bg-gradient-to-r from-[#0f5e7d] via-[#0b4d6f] to-[#0c7ca8] px-5 text-white shadow-sm">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex h-7 w-7 items-center justify-center rounded-sm bg-white/10 ring-1 ring-white/20">
                        <span class="flex gap-1">
                            <span class="block h-3.5 w-1 rounded-sm bg-white/90" />
                            <span class="block h-3.5 w-1 rounded-sm bg-white/80" />
                            <span class="block h-3.5 w-1 rounded-sm bg-white/70" />
                        </span>
                    </div>
                    <div class="truncate text-[15px] font-semibold tracking-tight sm:text-[17px]">Dynamics 365 Business Central</div>
                </div>
                <div class="flex items-center gap-2 text-white/80 sm:gap-3">
                    <button class="flex h-8 w-8 items-center justify-center rounded-md bg-white/5 hover:bg-white/10" type="button" aria-label="Notifications">
                        <span class="text-sm">◌</span>
                    </button>
                    <button class="flex h-8 w-8 items-center justify-center rounded-md bg-white/5 hover:bg-white/10" type="button" aria-label="Search">
                        <span class="text-sm">⌕</span>
                    </button>
                    <button class="flex h-8 w-8 items-center justify-center rounded-md bg-white/5 hover:bg-white/10" type="button" aria-label="Settings">
                        <span class="text-sm">⚙</span>
                    </button>
                    <button class="flex h-8 w-8 items-center justify-center rounded-md bg-white/5 hover:bg-white/10" type="button" aria-label="Profile">
                        <span class="text-sm">◉</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between border-b border-slate-200 bg-[#f6f8fb] px-5 py-2.5">
                <nav class="flex min-w-0 flex-wrap items-center gap-4 text-[14px] font-medium text-slate-600">
                    <span class="text-[#1d2a3b]">CRONUS USA, Inc.</span>
                    <div class="hidden items-center gap-4 md:flex">
                        <button v-for="tab in primaryTabs" :key="tab" type="button" class="rounded-sm px-1 py-1.5 transition hover:text-[#0d5d74]" :class="tab === 'Sales' ? 'text-[#1c2d3d] underline decoration-[#1bb5c2] decoration-2 underline-offset-8' : ''">
                            {{ tab }}
                        </button>
                    </div>
                </nav>
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#d6f7f9] text-[11px] font-bold text-[#0c6d70]">EA</div>
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#dbe7ff] text-[11px] font-bold text-[#2e4d8b]">PA</div>
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#dffaf2] text-[11px] font-bold text-[#1a7d5d]">SO</div>
                </div>
            </div>
        </div>

        <div class="border-b border-slate-200 bg-white/75 px-5 py-2.5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-[13px] text-slate-600">
                    <span class="font-medium">Sales Invoices:</span>
                    <span class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-slate-700">
                        All
                        <span class="text-[10px] text-slate-500">▾</span>
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-[13px] text-slate-600">
                    <button
                        v-for="action in listActions"
                        :key="action"
                        type="button"
                        class="rounded-sm border border-slate-200 bg-white px-2.5 py-1.5 font-medium shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                        :class="action === 'New' || action === 'Post' ? 'bg-[#0d5d74] text-white hover:bg-[#0d4d62] hover:text-white' : ''"
                    >
                        {{ action }}
                    </button>
                </div>
            </div>
        </div>

        <div class="flex min-h-[680px] bg-[#f3f5f7]">
            <div class="flex-1 border-r border-slate-200 bg-white">
                <div class="overflow-hidden border-b border-slate-200 bg-[#f7f9fc] text-[12px] text-slate-600">
                    <div class="grid grid-cols-[95px_1.7fr_1.1fr_1.1fr_1.1fr_1fr_1fr] gap-2 px-3 py-2.5 font-semibold uppercase tracking-wide text-slate-500">
                        <span>No.</span>
                        <span>Customer</span>
                        <span>Contact</span>
                        <span>Posting Date</span>
                        <span>Due Date</span>
                        <span>Amount</span>
                        <span>User ID</span>
                    </div>
                </div>

                <div class="divide-y divide-slate-200">
                    <button
                        v-for="(invoice, index) in invoiceRows"
                        :key="invoice.id"
                        type="button"
                        @click="selection = index"
                        class="grid w-full grid-cols-[95px_1.7fr_1.1fr_1.1fr_1.1fr_1fr_1fr] gap-2 px-3 py-2.5 text-left text-[12px] leading-5 transition hover:bg-[#edf9fa]"
                        :class="selection === index ? 'bg-[#dff3f4]' : 'bg-transparent'"
                    >
                        <span class="font-medium text-[#1c6181]">{{ invoice.no }}</span>
                        <span class="truncate text-slate-700">{{ invoice.customer.name }}</span>
                        <span class="truncate text-slate-700">Robert Townes</span>
                        <span class="text-slate-700">{{ formatDate(invoice.invoice_date) }}</span>
                        <span class="text-slate-700">{{ formatDate(invoice.due_date) }}</span>
                        <span class="font-medium text-slate-700">{{ invoice.grand_total }}</span>
                        <span class="text-slate-700">10,731.60</span>
                    </button>
                </div>
            </div>

            <aside class="w-[310px] bg-[#f5f6f8] p-0 text-slate-700">
                <div class="border-b border-slate-200 bg-[#f7f8fa] px-3 py-2.5">
                    <div class="flex items-center justify-between text-[13px] font-medium">
                        <span class="text-slate-700">Details</span>
                        <span class="flex items-center gap-2 rounded bg-white px-2 py-1 text-[#0d5d74] shadow-sm ring-1 ring-slate-200">Attachments (0)</span>
                    </div>
                </div>

                <div class="border-b border-slate-200 bg-white/30 p-3">
                    <div class="mb-2 text-[12px] font-medium uppercase tracking-wide text-slate-500">Selected invoice</div>
                    <div v-if="selectedInvoice" class="space-y-2 text-[12px] text-slate-600">
                        <div class="flex justify-between gap-3"><span class="text-slate-500">No.</span><span class="font-medium text-slate-800">{{ selectedInvoice.no }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Customer</span><span class="font-medium text-slate-800">{{ selectedInvoice.customer.name }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Posting</span><span>{{ formatDate(selectedInvoice.invoice_date) }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Due</span><span>{{ formatDate(selectedInvoice.due_date) }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Total</span><span class="font-medium text-slate-800">{{ selectedInvoice.grand_total }}</span></div>
                    </div>
                    <div v-else class="rounded border border-dashed border-slate-200 bg-slate-50 p-3 text-[12px] text-slate-500">
                        There is nothing to show in this view
                    </div>
                </div>

                <div class="space-y-3 p-3">
                    <div class="flex items-center justify-between text-[13px] font-medium text-slate-700">
                        <span>Notes</span>
                        <button class="text-lg font-normal text-slate-500" type="button">＋</button>
                    </div>
                    <div class="rounded border border-dashed border-slate-200 bg-slate-50 p-3 text-[12px] text-slate-500">
                        There is nothing to show in this view
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>
