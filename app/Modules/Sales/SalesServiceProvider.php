<?php

namespace App\Modules\Sales;

use App\Modules\Finance\Domain\PostingRuleRegistry;
use App\Modules\Sales\Domain\PostingRules\CreditNotePostingRule;
use App\Modules\Sales\Domain\PostingRules\InvoicePaymentPostingRule;
use App\Modules\Sales\Domain\PostingRules\InvoicePostingRule;
use Illuminate\Support\ServiceProvider;

class SalesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $registry = $this->app->make(PostingRuleRegistry::class);
        $registry->register('invoice.issued', $this->app->make(InvoicePostingRule::class));
        $registry->register('invoice_payment.recorded', $this->app->make(InvoicePaymentPostingRule::class));
        $registry->register('credit_note.posted', $this->app->make(CreditNotePostingRule::class));
    }
}
