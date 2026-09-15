<?php

namespace App\Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Warehouse\Actions\AssignWarehouseManager;
use App\Modules\Warehouse\Actions\RegisterWarehouse;
use App\Modules\Warehouse\Http\Requests\StoreWarehouseRequest;
use App\Modules\Warehouse\Http\Requests\UpdateWarehouseRequest;
use App\Modules\Warehouse\Models\Warehouse;
use App\Support\AdjacentRecordResolver;
use App\Support\ListPageProps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(Request $request, ListPageProps $listPageProps): Response
    {
        Gate::authorize('viewAny', Warehouse::class);

        $sort = $listPageProps->resolveSort($request, ['name', 'code', 'created_at'], 'name');
        $direction = $listPageProps->resolveDirection($request);
        $search = $request->string('q')->toString();

        $paginator = Warehouse::query()
            ->with('manager')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")))
            ->orderBy($sort, $direction)
            ->paginate($listPageProps->resolvePerPage($request))
            ->withQueryString();

        return Inertia::render('warehouses/Index', [
            'warehouses' => $paginator->items(),
            ...$listPageProps->build($paginator, $request),
        ]);
    }

    public function show(Warehouse $warehouse, AdjacentRecordResolver $adjacent): Response
    {
        Gate::authorize('view', $warehouse);

        $warehouse->load(['manager', 'vans' => fn ($query) => $query->with('dsr')->orderBy('code')]);

        return Inertia::render('warehouses/Show', [
            'warehouse' => $warehouse,
            ...$adjacent->resolve(Warehouse::query(), $warehouse, 'name'),
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
