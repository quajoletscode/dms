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
use App\Support\AdjacentRecordResolver;
use App\Support\ListPageProps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request, ListPageProps $listPageProps): Response
    {
        Gate::authorize('viewAny', Product::class);

        $sort = $listPageProps->resolveSort($request, ['name', 'sku', 'created_at'], 'name');
        $direction = $listPageProps->resolveDirection($request);
        $search = $request->string('q')->toString();

        $paginator = Product::query()
            ->with(['category', 'unit'])
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->orderBy($sort, $direction)
            ->paginate($listPageProps->resolvePerPage($request))
            ->withQueryString();

        return Inertia::render('products/Index', [
            'products' => $paginator->items(),
            ...$listPageProps->build($paginator, $request),
        ]);
    }

    public function show(Product $product, AdjacentRecordResolver $adjacent): Response
    {
        Gate::authorize('view', $product);

        $product->load(['category', 'unit']);

        return Inertia::render('products/Show', [
            'product' => [
                ...$product->only(['id', 'sku', 'barcode', 'name', 'tax_rate', 'track_expiry', 'is_active', 'created_at', 'updated_at']),
                'category' => $product->category,
                'unit' => $product->unit,
                'cost_price' => $product->cost_price->toMajor(),
                'wholesale_price' => $product->wholesale_price->toMajor(),
                'retail_price' => $product->retail_price->toMajor(),
                'van_price' => $product->van_price->toMajor(),
                'reorder_level' => (string) $product->reorder_level,
            ],
            ...$adjacent->resolve(Product::query(), $product, 'name'),
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
