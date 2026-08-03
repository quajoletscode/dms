<?php

namespace App\Modules\Sales\Domain\PostingRules;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\MasterData\Models\Customer;
use App\Modules\Sales\Models\InvoicePayment;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * A payment reduces the customer's Accounts Receivable balance: Dr Cash on
 * Hand / Cr AR (customer). All payment methods post to the same Cash on
 * Hand account for now — a Bank-account-per-method breakdown is Phase 6
 * (BNK-01..06).
 */
final class InvoicePaymentPostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof InvoicePayment) {
            throw new InvalidArgumentException('InvoicePaymentPostingRule can only post InvoicePayment documents.');
        }

        $customerId = $document->invoice->customer_id;

        return [
            JournalLineData::debit($this->accounts->cashOnHand()->id, $document->amount),
            JournalLineData::credit($this->accounts->accountsReceivable()->id, $document->amount, Customer::class, $customerId),
        ];
    }
}
