<?php

namespace App\Modules\MasterData;

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Policies\CustomerPolicy;
use App\Modules\MasterData\Policies\ProductPolicy;
use App\Modules\MasterData\Policies\SupplierPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class MasterDataServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
    }
}
