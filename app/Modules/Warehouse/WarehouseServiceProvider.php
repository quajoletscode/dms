<?php

namespace App\Modules\Warehouse;

use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Policies\WarehousePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class WarehouseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Warehouse::class, WarehousePolicy::class);
    }
}
