<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\RecordInvoicePayment;
use App\Modules\Sales\Http\Requests\RecordInvoicePaymentRequest;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Warehouse\Domain\WarehouseScope;
use App\Support\AdjacentRecordResolver;
use App\Support\ListPageProps;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request, ListPageProps $listPageProps): Response
    {
        Gate::authorize('viewAny', Invoice::class);

        $user = Auth::user();
        $canViewAll = $user->can('invoice.create') || $user->can('invoice.payment.record');

        $sort = $listPageProps->resolveSort($request, ['no', 'status', 'grand_total', 'invoice_date', 'created_at'], 'created_at');
        $direction = $listPageProps->resolveDirection($request, 'desc');
        $search = $request->string('q')->toString();

        $paginator = Invoice::query()
            ->when(! $canViewAll, fn ($query) => $query->where('created_by', $user->id))
            ->with('customer')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('no', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"))))
            ->orderBy($sort, $direction)
            ->paginate($listPageProps->resolvePerPage($request))
            ->withQueryString()
            ->through(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'no' => $invoice->no,
                'source' => $invoice->source,
                'status' => $invoice->status,
                'invoice_date' => $invoice->invoice_date?->toDateString(),
                'grand_total' => $invoice->grand_total->toMajor(),
                'customer' => $invoice->customer->only(['id', 'name']),
            ]);

        return Inertia::render('sales/invoices/Index', [
            'invoices' => $paginator->items(),
            ...$listPageProps->build($paginator, $request),
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
            'items.glAccount',
            'payments.receivedBy',
        ]);

        return Inertia::render('sales/invoices/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'no' => $invoice->no,
                'source' => $invoice->source,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date?->toDateString(),
                'invoice_date' => $invoice->invoice_date?->toDateString(),
                'posting_date' => $invoice->posting_date?->toDateString(),
                'subtotal' => $invoice->subtotal->toMajor(),
                'tax_total' => $invoice->tax_total->toMajor(),
                'discount_total' => $invoice->discount_total->toMajor(),
                'grand_total' => $invoice->grand_total->toMajor(),
                'customer' => $invoice->customer->only(['id', 'name']),
                'warehouse' => $invoice->warehouse->only(['id', 'name']),
                'items' => $invoice->items->map(fn ($item) => [
                    'id' => $item->id,
                    'line_type' => $item->line_type,
                    'product' => $item->product?->only(['id', 'sku', 'name']),
                    'gl_account' => $item->glAccount?->only(['id', 'code', 'name']),
                    'service_date' => $item->service_date?->toDateString(),
                    'vehicle_no' => $item->vehicle_no,
                    'line_description' => $item->line_description,
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
