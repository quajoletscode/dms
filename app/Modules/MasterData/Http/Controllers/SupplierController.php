<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Actions\CreateSupplier;
use App\Modules\MasterData\Http\Requests\StoreSupplierRequest;
use App\Modules\MasterData\Http\Requests\UpdateSupplierRequest;
use App\Modules\MasterData\Models\Supplier;
use App\Support\AdjacentRecordResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Supplier::class);

        return Inertia::render('suppliers/Index', [
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function show(Supplier $supplier, AdjacentRecordResolver $adjacent): Response
    {
        Gate::authorize('view', $supplier);

        return Inertia::render('suppliers/Show', [
            'supplier' => [
                ...$supplier->only(['id', 'code', 'name', 'contact', 'payment_terms', 'is_active', 'created_at', 'updated_at']),
                'opening_balance' => $supplier->opening_balance->toMajor(),
            ],
            ...$adjacent->resolve(Supplier::query(), $supplier, 'name'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Supplier::class);

        return Inertia::render('suppliers/Create');
    }

    public function store(StoreSupplierRequest $request, CreateSupplier $action): RedirectResponse
    {
        $action->execute($request->validated());

        return to_route('suppliers.index')->with('success', 'Supplier created.');
    }

    public function edit(Supplier $supplier): Response
    {
        Gate::authorize('update', $supplier);

        return Inertia::render('suppliers/Edit', [
            'supplier' => [
                ...$supplier->only(['id', 'code', 'name', 'contact', 'payment_terms', 'is_active']),
                'opening_balance' => $supplier->opening_balance->toMajor(),
            ],
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->safe()->only(['code', 'name', 'contact', 'payment_terms', 'is_active']));

        return to_route('suppliers.index')->with('success', 'Supplier updated.');
    }
}
