<?php

namespace App\Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Warehouse\Actions\AssignWarehouseManager;
use App\Modules\Warehouse\Actions\RegisterWarehouse;
use App\Modules\Warehouse\Http\Requests\StoreWarehouseRequest;
use App\Modules\Warehouse\Http\Requests\UpdateWarehouseRequest;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Warehouse::class);

        return Inertia::render('warehouses/Index', [
            'warehouses' => Warehouse::query()->with('manager')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Warehouse::class);

        return Inertia::render('warehouses/Create', [
            'managers' => User::query()->role('warehouse_manager')->get(['id', 'name']),
        ]);
    }

    public function store(StoreWarehouseRequest $request, RegisterWarehouse $action, AssignWarehouseManager $assignManager): RedirectResponse
    {
        $warehouse = $action->execute($request->validated());

        if ($request->validated('manager_id')) {
            $assignManager->execute($warehouse, User::query()->whereKey($request->validated('manager_id'))->firstOrFail());
        }

        return to_route('warehouses.index')->with('success', 'Warehouse created.');
    }

    public function edit(Warehouse $warehouse): Response
    {
        Gate::authorize('update', $warehouse);

        return Inertia::render('warehouses/Edit', [
            'warehouse' => $warehouse,
            'managers' => User::query()->role('warehouse_manager')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse, AssignWarehouseManager $assignManager): RedirectResponse
    {
        $warehouse->update($request->safe()->only(['code', 'name', 'location', 'is_active']));

        if ($request->validated('manager_id')) {
            $assignManager->execute($warehouse, User::query()->whereKey($request->validated('manager_id'))->firstOrFail());
        }

        return to_route('warehouses.index')->with('success', 'Warehouse updated.');
    }
}
