<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\RecordInvoicePayment;
use App\Modules\Sales\Http\Requests\RecordInvoicePaymentRequest;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Warehouse\Domain\WarehouseScope;
use App\Support\AdjacentRecordResolver;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Invoice::class);

        $user = Auth::user();
        $canViewAll = $user->can('invoice.create') || $user->can('invoice.payment.record');

        return Inertia::render('sales/invoices/Index', [
            'invoices' => Invoice::query()
                ->when(! $canViewAll, fn ($query) => $query->where('created_by', $user->id))
                ->with('customer')
                ->latest()
                ->get()
                ->map(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'no' => $invoice->no,
                    'source' => $invoice->source,
                    'status' => $invoice->status,
                    'grand_total' => $invoice->grand_total->toMajor(),
                    'customer' => $invoice->customer->only(['id', 'name']),
                ]),
        ]);
    }

    public function show(Invoice $invoice, AdjacentRecordResolver $adjacent): Response
    {
        Gate::authorize('view', $invoice);

        // A viewer isn't necessarily pivoted to this invoice's warehouse (a
        // cashier/DSR only ever sees invoices they created) — WarehouseScope
        // would otherwise resolve this relation to null, same gotcha as
        // PurchaseOrderController.
        $invoice->load([
            'customer',
            'warehouse' => fn ($query) => $query->withoutGlobalScope(WarehouseScope::class),
            'items.product',
            'payments.receivedBy',
        ]);

        return Inertia::render('sales/invoices/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'no' => $invoice->no,
                'source' => $invoice->source,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date?->toDateString(),
                'subtotal' => $invoice->subtotal->toMajor(),
                'tax_total' => $invoice->tax_total->toMajor(),
                'discount_total' => $invoice->discount_total->toMajor(),
                'grand_total' => $invoice->grand_total->toMajor(),
                'customer' => $invoice->customer->only(['id', 'name']),
                'warehouse' => $invoice->warehouse->only(['id', 'name']),
                'items' => $invoice->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product' => $item->product->only(['id', 'sku', 'name']),
                    'qty' => (string) $item->qty,
                    'unit_price' => $item->unit_price->toMajor(),
                    'discount' => $item->discount->toMajor(),
                    'tax' => $item->tax->toMajor(),
                ]),
                'payments' => $invoice->payments->map(fn ($payment) => [
                    'id' => $payment->id,
                    'method' => $payment->method,
                    'amount' => $payment->amount->toMajor(),
                    'reference' => $payment->reference,
                    'received_by' => $payment->receivedBy?->only(['id', 'name']),
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                ]),
            ],
            'canRecordPayment' => Auth::user()?->can('invoice.payment.record') ?? false,
            ...$adjacent->resolve(Invoice::query(), $invoice, 'no'),
        ]);
    }

    public function recordPayment(RecordInvoicePaymentRequest $request, Invoice $invoice, RecordInvoicePayment $action): RedirectResponse
    {
        $action->execute(
            $invoice,
            Money::fromMajor($request->validated('amount')),
            $request->validated('method'),
            $request->validated('reference'),
            $request->validated('client_uuid'),
        );

        return back()->with('success', 'Payment recorded.');
    }
}
