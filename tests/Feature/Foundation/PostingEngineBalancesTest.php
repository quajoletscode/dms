<?php

use App\Modules\Finance\Domain\Contracts\PostingRule;
use App\Modules\Finance\Domain\JournalLineData;
use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Finance\Domain\PostingRuleRegistry;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Support\Exceptions\UnbalancedJournalException;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;

function createAccount(string $code, string $type): ChartOfAccount
{
    return ChartOfAccount::query()->create([
        'code' => $code,
        'name' => $code,
        'type' => $type,
    ]);
}

test('a balanced posting rule persists a journal atomically with its source document', function () {
    $debitAccount = createAccount('1000', 'asset');
    $creditAccount = createAccount('2000', 'liability');
    $document = createAccount('DOC-1', 'asset');

    $rule = new class($debitAccount, $creditAccount) implements PostingRule
    {
        public function __construct(private ChartOfAccount $debitAccount, private ChartOfAccount $creditAccount) {}

        public function resolveLines(Model $document): array
        {
            $amount = Money::fromMajor('100.00');

            return [
                JournalLineData::debit($this->debitAccount->id, $amount),
                JournalLineData::credit($this->creditAccount->id, $amount),
            ];
        }
    };

    app(PostingRuleRegistry::class)->register('test.balanced', $rule);

    $journal = app(PostingEngine::class)->post('test.balanced', $document);

    expect($journal->status)->toBe('posted')
        ->and($journal->lines()->count())->toBe(2)
        ->and($journal->postable_type)->toBe($document->getMorphClass())
        ->and($journal->postable_id)->toBe($document->id);

    $this->assertDatabaseCount('journal_lines', 2);
});

test('an unbalanced posting rule throws and persists nothing', function () {
    $debitAccount = createAccount('1001', 'asset');
    $creditAccount = createAccount('2001', 'liability');
    $document = createAccount('DOC-2', 'asset');

    $rule = new class($debitAccount, $creditAccount) implements PostingRule
    {
        public function __construct(private ChartOfAccount $debitAccount, private ChartOfAccount $creditAccount) {}

        public function resolveLines(Model $document): array
        {
            return [
                JournalLineData::debit($this->debitAccount->id, Money::fromMajor('100.00')),
                JournalLineData::credit($this->creditAccount->id, Money::fromMajor('50.00')),
            ];
        }
    };

    app(PostingRuleRegistry::class)->register('test.unbalanced', $rule);

    expect(fn () => app(PostingEngine::class)->post('test.unbalanced', $document))
        ->toThrow(UnbalancedJournalException::class);

    $this->assertDatabaseCount('journal_entries', 0);
    $this->assertDatabaseCount('journal_lines', 0);
});

test('posting with an unregistered event type throws', function () {
    $document = createAccount('DOC-3', 'asset');

    expect(fn () => app(PostingEngine::class)->post('no.such.rule', $document))
        ->toThrow(InvalidArgumentException::class);
});
