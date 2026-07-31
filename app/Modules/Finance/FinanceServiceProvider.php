<?php

namespace App\Modules\Finance;

use App\Modules\Finance\Domain\PostingRuleRegistry;
use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PostingRuleRegistry::class);
    }

    /**
     * Business-document PostingRules (GrnPostingRule, InvoicePostingRule, ...)
     * are registered here as each module introduces a document that posts.
     */
    public function boot(): void
    {
        //
    }
}
