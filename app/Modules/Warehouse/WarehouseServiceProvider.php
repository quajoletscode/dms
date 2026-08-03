<?php

namespace App\Modules\Warehouse;

use App\Modules\Finance\Domain\PostingRuleRegistry;
use App\Modules\Warehouse\Domain\PostingRules\GrnPostingRule;
use App\Modules\Warehouse\Domain\PostingRules\PurchaseReturnPostingRule;
use App\Modules\Warehouse\Models\PurchaseOrder;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Policies\PurchaseOrderPolicy;
use App\Modules\Warehouse\Policies\WarehousePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class WarehouseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Warehouse::class, WarehousePolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);

        $registry = $this->app->make(PostingRuleRegistry::class);
        $registry->register('grn.posted', $this->app->make(GrnPostingRule::class));
        $registry->register('purchase_return.posted', $this->app->make(PurchaseReturnPostingRule::class));
    }
}
