<?php

namespace App\Modules\Van;

use App\Modules\Finance\Domain\PostingRuleRegistry;
use App\Modules\Van\Domain\PostingRules\StockWriteOffPostingRule;
use App\Modules\Van\Models\VanStorage;
use App\Modules\Van\Policies\VanPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class VanServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(VanStorage::class, VanPolicy::class);

        $registry = $this->app->make(PostingRuleRegistry::class);
        $registry->register('stock.write_off', $this->app->make(StockWriteOffPostingRule::class));
    }
}
