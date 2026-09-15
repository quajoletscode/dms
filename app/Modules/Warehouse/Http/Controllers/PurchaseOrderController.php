<?php

namespace App\Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\Warehouse\Actions\ApprovePurchaseOrder;
use App\Modules\Warehouse\Actions\CancelPurchaseOrder;
use App\Modules\Warehouse\Actions\CreatePurchaseOrder;
use App\Modules\Warehouse\Actions\SubmitPurchaseOrder;
use App\Modules\Warehouse\Domain\WarehouseScope;
use App\Modules\Warehouse\Http\Requests\StorePurchaseOrderRequest;
use App\Modules\Warehouse\Models\PurchaseOrder;
use App\Modules\Warehouse\Models\Warehouse;
use App\Support\AdjacentRecordResolver;
use App\Support\ListPageProps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request, ListPageProps $listPageProps): Response
    {
        Gate::authorize('viewAny', PurchaseOrder::class);

        $sort = $listPageProps->resolveSort($request, ['no', 'status', 'grand_total', 'created_at'], 'created_at');
        $direction = $listPageProps->resolveDirection($request, 'desc');
        $search = $request->string('q')->toString();

        $paginator = PurchaseOrder::query()
            // A PO's warehouse is loaded without WarehouseScope: the policy already
            // decides who may see this PO at all (po.create/po.approve), so a
            // manager assigned to a *different* warehouse should still see the name
            // here rather than a silently-null relation.
            ->with(['supplier', 'warehouse' => fn ($query) => $query->withoutGlobalScope(WarehouseScope::class)])
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('no', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery->where('name', 'like', "%{$search}%"))))
            ->orderBy($sort, $direction)
            ->paginate($listPageProps->resolvePerPage($request))
            ->withQueryString()
            ->through(fn (PurchaseOrder $po) => [
                'id' => $po->id,
                'no' => $po->no,
                'status' => $po->status,
                'grand_total' => $po->grand_total->toMajor(),
                'supplier' => $po->supplier->only(['id', 'name']),
                'warehouse' => $po->warehouse->only(['id', 'name']),
            ]);

        return Inertia::render('purchase-orders/Index', [
            'purchaseOrders' => $paginator->items(),
            ...$listPageProps->build($paginator, $request),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', PurchaseOrder::class);

        return Inertia::render('purchase-orders/Create', [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name', 'cost_price'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'cost_price' => $product->cost_price->toMajor(),
                ]),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request, CreatePurchaseOrder $action): RedirectResponse
    {
        $po = $action->execute(
            $request->validated('supplier_id'),
            $request->validated('warehouse_id'),
            $request->validated('items'),
            $request->validated('expected_delivery_date'),
        );

        return to_route('purchase-orders.show', $po)->with('success', "Purchase order {$po->no} created.");
    }

    public function show(PurchaseOrder $purchaseOrder, AdjacentRecordResolver $adjacent): Response
    {
        Gate::authorize('view', $purchaseOrder);

        $purchaseOrder->load([
            'supplier',
            'warehouse' => fn ($query) => $query->withoutGlobalScope(WarehouseScope::class),
            'creator',
            'approver',
            'items.product',
        ]);

        return Inertia::render('purchase-orders/Show', [
            'purchaseOrder' => [
                'id' => $purchaseOrder->id,
                'no' => $purchaseOrder->no,
                'status' => $purchaseOrder->status,
                'expected_delivery_date' => $purchaseOrder->expected_delivery_date?->toDateString(),
                'subtotal' => $purchaseOrder->subtotal->toMajor(),
                'tax_total' => $purchaseOrder->tax_total->toMajor(),
                'discount_total' => $purchaseOrder->discount_total->toMajor(),
                'grand_total' => $purchaseOrder->grand_total->toMajor(),
                'supplier' => $purchaseOrder->supplier->only(['id', 'name']),
                'warehouse' => $purchaseOrder->warehouse->only(['id', 'name']),
                'creator' => $purchaseOrder->creator?->only(['id', 'name']),
                'approver' => $purchaseOrder->approver?->only(['id', 'name']),
                'items' => $purchaseOrder->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product' => $item->product->only(['id', 'sku', 'name']),
                    'qty_ordered' => (string) $item->qty_ordered,
                    'qty_received' => (string) $item->qty_received,
                    'unit_cost' => $item->unit_cost->toMajor(),
                    'discount' => $item->discount->toMajor(),
                    'tax' => $item->tax->toMajor(),
                ]),
            ],
            ...$adjacent->resolve(PurchaseOrder::query(), $purchaseOrder, 'no'),
        ]);
    }

    public function submit(PurchaseOrder $purchaseOrder, SubmitPurchaseOrder $action): RedirectResponse
    {
        Gate::authorize('update', $purchaseOrder);

        $action->execute($purchaseOrder);

        return back()->with('success', "Purchase order {$purchaseOrder->no} submitted.");
    }

    public function approve(PurchaseOrder $purchaseOrder, ApprovePurchaseOrder $action): RedirectResponse
    {
        Gate::authorize('view', $purchaseOrder);

        $action->execute($purchaseOrder);

        return back()->with('success', "Purchase order {$purchaseOrder->no} approved.");
    }

    public function cancel(PurchaseOrder $purchaseOrder, CancelPurchaseOrder $action): RedirectResponse
    {
        Gate::authorize('update', $purchaseOrder);

        $action->execute($purchaseOrder);

        return back()->with('success', "Purchase order {$purchaseOrder->no} cancelled.");
    }
}
