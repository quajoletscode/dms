<?php

namespace App\Modules\Sales\Domain\PostingRules;

use App\Modules\Finance\Domain\ChartOfAccountResolver;
use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\Sales\Models\TillSession;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * A till's counted float differs from its expected float. Shortage (counted
 * < expected): Dr Cash Over/Short, Cr Cash on Hand — cash physically missing.
 * Overage (counted > expected): Dr Cash on Hand, Cr Cash Over/Short — extra
 * cash found. CloseTillSession only posts this event when variance is
 * non-zero; a balanced till posts nothing.
 */
final class TillVariancePostingRule implements PostingRule
{
    public function __construct(private readonly ChartOfAccountResolver $accounts) {}

    public function resolveLines(Model $document): array
    {
        if (! $document instanceof TillSession) {
            throw new InvalidArgumentException('TillVariancePostingRule can only post TillSession documents.');
        }

        $variance = $document->variance ?? throw new InvalidArgumentException('TillSession has no variance to post.');
        $amount = $variance->isNegative() ? $variance->negate() : $variance;

        if ($variance->isNegative()) {
            return [
                JournalLineData::debit($this->accounts->cashOverShort()->id, $amount),
                JournalLineData::credit($this->accounts->cashOnHand()->id, $amount),
            ];
        }

        return [
            JournalLineData::debit($this->accounts->cashOnHand()->id, $amount),
            JournalLineData::credit($this->accounts->cashOverShort()->id, $amount),
        ];
    }
}
