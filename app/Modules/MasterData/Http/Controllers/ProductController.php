<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Actions\CreateProduct;
use App\Modules\MasterData\Actions\UpdateProductPricing;
use App\Modules\MasterData\Http\Requests\StoreProductRequest;
use App\Modules\MasterData\Http\Requests\UpdateProductRequest;
use App\Modules\MasterData\Models\Category;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Product::class);

        return Inertia::render('products/Index', [
            'products' => Product::query()->with(['category', 'unit'])->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Product::class);

        return Inertia::render('products/Create', [
            'units' => Unit::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductRequest $request, CreateProduct $action): RedirectResponse
    {
        $action->execute($request->validated());

        return to_route('products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): Response
    {
        Gate::authorize('update', $product);

        return Inertia::render('products/Edit', [
            'product' => [
                ...$product->only(['id', 'sku', 'barcode', 'name', 'category_id', 'unit_id', 'tax_rate', 'track_expiry', 'is_active']),
                'cost_price' => $product->cost_price->toMajor(),
                'wholesale_price' => $product->wholesale_price->toMajor(),
                'retail_price' => $product->retail_price->toMajor(),
                'van_price' => $product->van_price->toMajor(),
                'reorder_level' => (string) $product->reorder_level,
            ],
            'units' => Unit::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductPricing $pricingAction): RedirectResponse
    {
        $product->update($request->safe()->only([
            'sku', 'barcode', 'name', 'category_id', 'unit_id', 'reorder_level', 'track_expiry', 'is_active',
        ]));

        $pricingAction->execute($product, $request->safe()->only([
            'cost_price', 'wholesale_price', 'retail_price', 'van_price', 'tax_rate',
        ]));

        return to_route('products.index')->with('success', 'Product updated.');
    }
}
