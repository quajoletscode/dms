<?php

namespace App\Modules\Van\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Van\Actions\ReassignVanDsr;
use App\Modules\Van\Actions\RegisterVan;
use App\Modules\Van\Domain\Exceptions\VanHandoverRequiredException;
use App\Modules\Van\Http\Requests\ReassignVanRequest;
use App\Modules\Van\Http\Requests\StoreVanRequest;
use App\Modules\Van\Models\VanStorage;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VanController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', VanStorage::class);

        return Inertia::render('vans/Index', [
            'vans' => VanStorage::query()->with(['warehouse', 'dsr'])->orderBy('code')->get(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', VanStorage::class);

        return Inertia::render('vans/Create', [
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'dsrs' => User::query()->role('dsr')->get(['id', 'name']),
        ]);
    }

    public function store(StoreVanRequest $request, RegisterVan $action): RedirectResponse
    {
        $action->execute($request->validated());

        return to_route('vans.index')->with('success', 'Van registered.');
    }

    public function edit(VanStorage $van): Response
    {
        Gate::authorize('update', $van);

        return Inertia::render('vans/Edit', [
            'van' => $van->load(['warehouse', 'dsr']),
            'dsrs' => User::query()->role('dsr')->get(['id', 'name']),
        ]);
    }

    public function update(ReassignVanRequest $request, VanStorage $van, ReassignVanDsr $action): RedirectResponse
    {
        try {
            $action->execute($van, User::query()->whereKey($request->validated('dsr_user_id'))->firstOrFail(), $request->validated('handover_note'));
        } catch (VanHandoverRequiredException $e) {
            return back()->withErrors(['dsr_user_id' => $e->getMessage()])->withInput();
        }

        return to_route('vans.index')->with('success', 'Van DSR reassigned.');
    }
}
