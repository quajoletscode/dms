<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\Sales\Actions\RecordPosSale;
use App\Modules\Sales\Domain\Exceptions\CreditLimitExceededException;
use App\Modules\Sales\Domain\Exceptions\DiscountOverrideRequiredException;
use App\Modules\Sales\Domain\Exceptions\TillSessionClosedException;
use App\Modules\Sales\Http\Requests\StorePosSaleRequest;
use App\Modules\Sales\Models\TillSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PosSaleController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        Gate::authorize('sales.pos');

        $tillSession = TillSession::query()->where('user_id', Auth::id())->where('status', 'open')->first();

        if (! $tillSession) {
            return to_route('till-sessions.create')->with('info', 'Open a till session before ringing up a sale.');
        }

        return Inertia::render('sales/pos/Create', [
            'tillSession' => $tillSession->only(['id', 'warehouse_id']),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'credit_limit'])
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'credit_limit' => $customer->credit_limit->toMajor(),
                ]),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name', 'retail_price', 'tax_rate'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'retail_price' => $product->retail_price->toMajor(),
                    'tax_rate' => $product->tax_rate,
                ]),
            'discountOverrideThresholdPercent' => (float) config('sales.pos_discount_override_threshold_percent'),
            'canOverrideDiscount' => Auth::user()?->can('pos.discount_override') ?? false,
        ]);
    }

    public function store(StorePosSaleRequest $request, RecordPosSale $action): RedirectResponse
    {
        $tillSession = TillSession::query()->where('user_id', Auth::id())->where('status', 'open')->first();

        if (! $tillSession) {
            return to_route('till-sessions.create')->with('error', 'Open a till session before ringing up a sale.');
        }

        try {
            $invoice = $action->execute(
                $tillSession->id,
                $request->validated('customer_id'),
                $request->validated('items'),
                $request->validated('payments') ?? [],
            );
        } catch (TillSessionClosedException $e) {
            return to_route('till-sessions.create')->with('error', $e->getMessage());
        } catch (CreditLimitExceededException $e) {
            return back()->withErrors(['customer_id' => $e->getMessage()])->withInput();
        } catch (DiscountOverrideRequiredException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return to_route('invoices.show', $invoice)->with('success', "Sale {$invoice->no} recorded.");
    }
}
