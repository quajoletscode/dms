<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Actions\CreateCustomer;
use App\Modules\MasterData\Http\Requests\StoreCustomerRequest;
use App\Modules\MasterData\Http\Requests\UpdateCustomerRequest;
use App\Modules\MasterData\Models\Customer;
use App\Modules\Sales\Domain\Reports\CustomerStatistics;
use App\Support\AdjacentRecordResolver;
use App\Support\ListPageProps;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request, ListPageProps $listPageProps): Response
    {
        Gate::authorize('viewAny', Customer::class);

        $sort = $listPageProps->resolveSort($request, ['name', 'code', 'type', 'created_at'], 'name');
        $direction = $listPageProps->resolveDirection($request);
        $search = $request->string('q')->toString();

        $paginator = Customer::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")))
            ->orderBy($sort, $direction)
            ->paginate($listPageProps->resolvePerPage($request))
            ->withQueryString();

        return Inertia::render('customers/Index', [
            'customers' => $paginator->items(),
            ...$listPageProps->build($paginator, $request),
        ]);
    }

    public function show(Customer $customer, AdjacentRecordResolver $adjacent, CustomerStatistics $statistics): Response
    {
        Gate::authorize('view', $customer);

        return Inertia::render('customers/Show', [
            'customer' => [
                ...$customer->only(['id', 'code', 'name', 'type', 'price_category', 'driver_vehicle_profiles', 'is_active', 'created_at', 'updated_at']),
                'credit_limit' => $customer->credit_limit->toMajor(),
                'driver_vehicle_profiles' => $customer->driver_vehicle_profiles ?? [],
            ],
            'statistics' => array_map(fn (Money $value) => $value->toMajor(), $statistics->for($customer)),
            ...$adjacent->resolve(Customer::query(), $customer, 'name'),
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
                ...$customer->only(['id', 'code', 'name', 'type', 'price_category', 'driver_vehicle_profiles', 'is_active']),
                'credit_limit' => $customer->credit_limit->toMajor(),
                'driver_vehicle_profiles' => $customer->driver_vehicle_profiles ?? [],
            ],
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->safe()->only(['code', 'name', 'type', 'price_category', 'driver_vehicle_profiles', 'is_active']));

        if ($request->validated('credit_limit') !== null) {
            $customer->update(['credit_limit' => Money::fromMajor($request->validated('credit_limit'))->minorUnits]);
        }

        return to_route('customers.index')->with('success', 'Customer updated.');
    }
}
