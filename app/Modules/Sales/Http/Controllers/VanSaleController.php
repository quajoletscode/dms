<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\Sales\Actions\RecordVanSale;
use App\Modules\Sales\Domain\Exceptions\CreditLimitExceededException;
use App\Modules\Sales\Http\Requests\StoreVanSaleRequest;
use App\Modules\Van\Domain\VanScope;
use App\Modules\Van\Models\VanStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VanSaleController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        Gate::authorize('van.sale');

        $ownVan = VanStorage::query()->where('dsr_user_id', Auth::id())->first();
        $canActOnBehalf = Auth::user()?->can('van.manage') ?? false;

        if (! $ownVan && ! $canActOnBehalf) {
            return to_route('dashboard')->with('info', 'No van is assigned to you yet.');
        }

        return Inertia::render('sales/van-sales/Create', [
            'ownVan' => $ownVan?->only(['id', 'code']),
            // Only a van.manage holder (e.g. a warehouse manager) can act on
            // behalf of a DSR; the picker is only populated in that case.
            'vans' => $canActOnBehalf
                ? VanStorage::query()->where('is_active', true)->with('dsr')->orderBy('code')->get()
                    ->map(fn (VanStorage $van) => [
                        'id' => $van->id,
                        'code' => $van->code,
                        'dsr' => $van->dsr?->only(['id', 'name']),
                    ])
                : [],
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'credit_limit', 'driver_vehicle_profiles'])
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'credit_limit' => $customer->credit_limit->toMajor(),
                    'driver_vehicle_profiles' => $customer->driver_vehicle_profiles ?? [],
                ]),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name', 'van_price', 'tax_rate'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'van_price' => $product->van_price->toMajor(),
                    'tax_rate' => $product->tax_rate,
                ]),
        ]);
    }

    public function store(StoreVanSaleRequest $request, RecordVanSale $action): RedirectResponse
    {
        // A manager acting on behalf of a DSR needs to resolve any van, not
        // just their own — VanScope only restricts users with the 'dsr' role,
        // so this bypass is a no-op for a manager anyway, but keeps the
        // lookup correct if a dsr-rolled user somehow also holds van.manage.
        $van = VanStorage::query()->withoutGlobalScope(VanScope::class)->whereKey($request->validated('van_storage_id'))->firstOrFail();

        $isOwnVan = $van->dsr_user_id === Auth::id();
        $canActOnBehalf = Auth::user()?->can('van.manage') ?? false;

        if (! $isOwnVan && ! $canActOnBehalf) {
            abort(403, 'You may only record sales for your own van.');
        }

        try {
            $invoice = $action->execute(
                $van->id,
                $request->validated('customer_id'),
                $request->validated('items'),
                $request->validated('payments') ?? [],
                $request->validated('sale_date'),
            );
        } catch (CreditLimitExceededException $e) {
            return back()->withErrors(['customer_id' => $e->getMessage()])->withInput();
        }

        return to_route('invoices.show', $invoice)->with('success', "Sale {$invoice->no} recorded.");
    }
}
