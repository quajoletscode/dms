<?php

namespace App\Modules\Sales;

use App\Modules\Finance\Domain\PostingRuleRegistry;
use App\Modules\Sales\Domain\PostingRules\CreditNotePostingRule;
use App\Modules\Sales\Domain\PostingRules\InvoicePaymentPostingRule;
use App\Modules\Sales\Domain\PostingRules\InvoicePostingRule;
use App\Modules\Sales\Domain\PostingRules\TillVariancePostingRule;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\TillSession;
use App\Modules\Sales\Policies\InvoicePolicy;
use App\Modules\Sales\Policies\TillSessionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class SalesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(TillSession::class, TillSessionPolicy::class);

        $registry = $this->app->make(PostingRuleRegistry::class);
        $registry->register('invoice.issued', $this->app->make(InvoicePostingRule::class));
        $registry->register('invoice_payment.recorded', $this->app->make(InvoicePaymentPostingRule::class));
        $registry->register('credit_note.posted', $this->app->make(CreditNotePostingRule::class));
        $registry->register('till.closed_with_variance', $this->app->make(TillVariancePostingRule::class));
    }
}
