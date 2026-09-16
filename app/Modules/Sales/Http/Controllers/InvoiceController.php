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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * An invoice's outstanding balance isn't a stored column (SRS SI-PAY-001's
     * amount_paid/balance concept) — it's derived live from its payments here,
     * the same way CustomerStatistics derives it, so it can never go stale.
     * Written as a correlated subquery (not withSum) so it's directly
     * ORDER BY-able as `balance_minor` alongside the other list columns.
     */
    private const BALANCE_EXPR = '(invoices.grand_total - coalesce((select sum(ip.amount) from invoice_payments ip where ip.invoice_id = invoices.id), 0))';

    public function index(Request $request, ListPageProps $listPageProps): Response
    {
        Gate::authorize('viewAny', Invoice::class);

        $user = Auth::user();
        $canViewAll = $user->can('invoice.create') || $user->can('invoice.payment.record');

        $sortColumns = [
            'no' => 'invoices.no',
            'customer' => 'c.name',
            'status' => 'invoices.status',
            'invoice_date' => 'invoices.invoice_date',
            'due_date' => 'invoices.due_date',
            'grand_total' => 'invoices.grand_total',
            'balance' => 'balance_minor',
            'created_at' => 'invoices.created_at',
        ];

        $sort = $listPageProps->resolveSort($request, array_keys($sortColumns), 'created_at');
        $direction = $listPageProps->resolveDirection($request, 'desc');
        $search = $request->string('q')->toString();
        $status = $request->string('status')->toString();
        $status = in_array($status, ['unpaid', 'partially_paid', 'paid', 'overdue'], true) ? $status : 'all';
        $today = now()->toDateString();
        $dueSoonEnd = now()->addDays(7)->toDateString();

        // Joined (not with()) so the customer name/code are directly
        // searchable, sortable and selectable without an N+1 relation load —
        // every invoice has exactly one customer, so the join can't duplicate
        // rows. created_by has no dedicated "salesperson" field in this
        // schema (SI-LST-001 asks for one), so the creator stands in for it.
        $scoped = Invoice::query()
            ->join('customers as c', 'c.id', '=', 'invoices.customer_id')
            ->leftJoin('users as u', 'u.id', '=', 'invoices.created_by')
            ->when(! $canViewAll, fn (Builder $query) => $query->where('invoices.created_by', $user->id))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $q) use ($search) {
                $q->where('invoices.no', 'like', "%{$search}%")
                    ->orWhere('c.name', 'like', "%{$search}%")
                    ->orWhere('c.code', 'like', "%{$search}%")
                    ->orWhereExists(function ($sub) use ($search) {
                        $sub->select('invoice_items.id')
                            ->from('invoice_items')
                            ->join('products', 'products.id', '=', 'invoice_items.product_id')
                            ->whereColumn('invoice_items.invoice_id', 'invoices.id')
                            ->where(fn ($p) => $p->where('products.name', 'like', "%{$search}%")
                                ->orWhere('products.sku', 'like', "%{$search}%"));
                    });
            }));

        // Live per-status counts for the filter chips (SI-LST-002) — scoped by
        // visibility/search but not by the status filter itself, so a user
        // can see every bucket's size while one is selected.
        $statusCounts = (clone $scoped)
            ->selectRaw('invoices.status as status, count(*) as aggregate')
            ->groupBy('invoices.status')
            ->pluck('aggregate', 'status');

        // One aggregate query for the summary tiles (SI-LST-006): total
        // outstanding, overdue, and due-within-7-days — each as an amount
        // and a document count.
        $tiles = (clone $scoped)->selectRaw(
            'count(*) as total_count,
            coalesce(sum('.self::BALANCE_EXPR.'), 0) as total_outstanding,
            coalesce(sum(case when invoices.status != \'paid\' and invoices.due_date is not null and invoices.due_date < ? then '.self::BALANCE_EXPR.' else 0 end), 0) as overdue_outstanding,
            count(case when invoices.status != \'paid\' and invoices.due_date is not null and invoices.due_date < ? then 1 end) as overdue_count,
            coalesce(sum(case when invoices.status != \'paid\' and invoices.due_date is not null and invoices.due_date between ? and ? then '.self::BALANCE_EXPR.' else 0 end), 0) as due_soon_outstanding,
            count(case when invoices.status != \'paid\' and invoices.due_date is not null and invoices.due_date between ? and ? then 1 end) as due_soon_count',
            [$today, $today, $today, $dueSoonEnd, $today, $dueSoonEnd]
        )->first();

        // Totals for the *filtered* set (SI-LST-005) — status filter applied,
        // search/visibility already baked into $scoped.
        $filteredTotals = (clone $scoped)
            ->when($status !== 'all', fn (Builder $query) => $this->applyStatusFilter($query, $status, $today))
            ->selectRaw('count(*) as document_count, coalesce(sum(invoices.grand_total), 0) as total_value, coalesce(sum('.self::BALANCE_EXPR.'), 0) as total_outstanding')
            ->first();

        $paginator = (clone $scoped)
            ->when($status !== 'all', fn (Builder $query) => $this->applyStatusFilter($query, $status, $today))
            ->select('invoices.*')
            ->addSelect(['c.name as customer_name', 'c.code as customer_code', 'u.name as created_by_name'])
            ->selectRaw(self::BALANCE_EXPR.' as balance_minor')
            ->orderBy($sortColumns[$sort], $direction)
            ->paginate($listPageProps->resolvePerPage($request))
            ->withQueryString()
            ->through(function (Invoice $invoice) use ($today) {
                $balance = Money::fromMinorUnits((int) $invoice->balance_minor);
                $isOverdue = $invoice->status !== 'paid'
                    && $invoice->due_date !== null
                    && $invoice->due_date->toDateString() < $today
                    && $balance->isPositive();

                return [
                    'id' => $invoice->id,
                    'no' => $invoice->no,
                    'source' => $invoice->source,
                    'status' => $invoice->status,
                    'invoice_date' => $invoice->invoice_date?->toDateString(),
                    'due_date' => $invoice->due_date?->toDateString(),
                    'grand_total' => $invoice->grand_total->toMajor(),
                    'balance' => $balance->toMajor(),
                    'is_overdue' => $isOverdue,
                    'days_overdue' => $isOverdue ? (int) now()->diffInDays($invoice->due_date, absolute: true) : null,
                    'customer' => [
                        'id' => $invoice->customer_id,
                        'name' => $invoice->customer_name,
                        'code' => $invoice->customer_code,
                    ],
                    'created_by' => $invoice->created_by_name,
                ];
            });

        return Inertia::render('sales/invoices/Index', [
            'invoices' => $paginator->items(),
            'statusFilter' => $status,
            'statusCounts' => [
                'all' => (int) $statusCounts->sum(),
                'unpaid' => (int) ($statusCounts['unpaid'] ?? 0),
                'partially_paid' => (int) ($statusCounts['partially_paid'] ?? 0),
                'paid' => (int) ($statusCounts['paid'] ?? 0),
                'overdue' => (int) ($tiles->overdue_count ?? 0),
            ],
            'tiles' => [
                'outstanding' => [
                    'amount' => Money::fromMinorUnits((int) $tiles->total_outstanding)->toMajor(),
                    'count' => (int) $tiles->total_count,
                ],
                'overdue' => [
                    'amount' => Money::fromMinorUnits((int) $tiles->overdue_outstanding)->toMajor(),
                    'count' => (int) $tiles->overdue_count,
                ],
                'due_soon' => [
                    'amount' => Money::fromMinorUnits((int) $tiles->due_soon_outstanding)->toMajor(),
                    'count' => (int) $tiles->due_soon_count,
                ],
            ],
            'filteredTotals' => [
                'document_count' => (int) $filteredTotals->document_count,
                'total_value' => Money::fromMinorUnits((int) $filteredTotals->total_value)->toMajor(),
                'total_outstanding' => Money::fromMinorUnits((int) $filteredTotals->total_outstanding)->toMajor(),
            ],
            ...$listPageProps->build($paginator, $request, ['status' => $status !== 'all' ? $status : null]),
        ]);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    private function applyStatusFilter(Builder $query, string $status, string $today): Builder
    {
        if ($status === 'overdue') {
            return $query->whereIn('invoices.status', ['unpaid', 'partially_paid'])
                ->whereNotNull('invoices.due_date')
                ->where('invoices.due_date', '<', $today);
        }

        return $query->where('invoices.status', $status);
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
