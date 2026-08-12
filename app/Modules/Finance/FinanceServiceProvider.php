<?php

namespace App\Modules\Finance;

use App\Modules\Finance\Domain\PostingRuleRegistry;
use App\Modules\Finance\Domain\PostingRules\BankDepositPostingRule;
use App\Modules\Finance\Domain\PostingRules\BankTransferPostingRule;
use App\Modules\Finance\Domain\PostingRules\BankWithdrawalPostingRule;
use App\Modules\Finance\Domain\PostingRules\DsrCashHandoverPostingRule;
use App\Modules\Finance\Domain\PostingRules\DsrCollectionPostingRule;
use App\Modules\Finance\Domain\PostingRules\ExpensePostingRule;
use App\Modules\Finance\Domain\PostingRules\JournalReversalPostingRule;
use App\Modules\Finance\Domain\PostingRules\ManualJournalPostingRule;
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
        $registry = $this->app->make(PostingRuleRegistry::class);
        $registry->register('journal.reversed', $this->app->make(JournalReversalPostingRule::class));
        $registry->register('manual_journal.posted', $this->app->make(ManualJournalPostingRule::class));
        $registry->register('bank.deposited', $this->app->make(BankDepositPostingRule::class));
        $registry->register('bank.withdrawn', $this->app->make(BankWithdrawalPostingRule::class));
        $registry->register('bank.transferred', $this->app->make(BankTransferPostingRule::class));
        $registry->register('dsr_cash.collected', $this->app->make(DsrCollectionPostingRule::class));
        $registry->register('dsr_cash.handed_over', $this->app->make(DsrCashHandoverPostingRule::class));
        $registry->register('expense.recorded', $this->app->make(ExpensePostingRule::class));
    }
}
