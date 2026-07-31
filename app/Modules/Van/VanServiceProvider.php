<?php

namespace App\Modules\Van;

use App\Modules\Van\Models\VanStorage;
use App\Modules\Van\Policies\VanPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class VanServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(VanStorage::class, VanPolicy::class);
    }
}
