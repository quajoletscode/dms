<?php

namespace App\Modules\Finance\Domain\PostingRules;

use App\Models\User;
use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\Finance\Models\Collection;
use App\Modules\MasterData\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * BNK-06: a DSR collects cash in the field against a previously-issued
 * (credit) invoice. The cash hasn't reached the business yet — it's with the
 * DSR — but the customer's AR balance is reduced immediately: Dr Cash in DSR
 * Hand (tagged to the DSR) / Cr Accounts Receivable (tagged to the customer).
 */
final class DsrCollectionPostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof Collection) {
            throw new InvalidArgumentException('DsrCollectionPostingRule can only post Collection documents.');
        }

        return [
            JournalLineData::debit($this->accounts->cashInDsrHand()->id, $document->amount, User::class, $document->dsr_user_id),
            JournalLineData::credit($this->accounts->accountsReceivable()->id, $document->amount, Customer::class, $document->customer_id),
        ];
    }
}
