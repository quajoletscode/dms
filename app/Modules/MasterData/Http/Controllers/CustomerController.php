<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Actions\CreateCustomer;
use App\Modules\MasterData\Http\Requests\StoreCustomerRequest;
use App\Modules\MasterData\Http\Requests\UpdateCustomerRequest;
use App\Modules\MasterData\Models\Customer;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Customer::class);

        return Inertia::render('customers/Index', [
            'customers' => Customer::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Customer::class);

        return Inertia::render('customers/Create');
    }

    public function store(StoreCustomerRequest $request, CreateCustomer $action): RedirectResponse
    {
        $action->execute($request->validated());

        return to_route('customers.index')->with('success', 'Customer created.');
    }

    public function edit(Customer $customer): Response
    {
        Gate::authorize('update', $customer);

        return Inertia::render('customers/Edit', [
            'customer' => [
                ...$customer->only(['id', 'code', 'name', 'type', 'price_category', 'is_active']),
                'credit_limit' => $customer->credit_limit->toMajor(),
            ],
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->safe()->only(['code', 'name', 'type', 'price_category', 'is_active']));

        if ($request->validated('credit_limit') !== null) {
            $customer->update(['credit_limit' => Money::fromMajor($request->validated('credit_limit'))->minorUnits]);
        }

        return to_route('customers.index')->with('success', 'Customer updated.');
    }
}
