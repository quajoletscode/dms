<?php

use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\JournalEntry;
use App\Modules\Finance\Models\JournalLine;
use App\Support\Exceptions\ImmutableRecordException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function createJournalLine(): JournalLine
{
    $account = ChartOfAccount::query()->create([
        'code' => 'ACC-'.uniqid(),
        'name' => 'Test Account',
        'type' => 'asset',
    ]);

    $journal = JournalEntry::query()->create([
        'no' => 'JRN-'.uniqid(),
        'date' => now()->toDateString(),
        'status' => 'posted',
    ]);

    return JournalLine::query()->create([
        'journal_entry_id' => $journal->id,
        'account_id' => $account->id,
        'debit' => 1000,
        'credit' => 0,
        'created_at' => now(),
    ]);
}

test('updating a journal line is blocked at the Eloquent layer', function () {
    $line = createJournalLine();

    expect(fn () => $line->update(['debit' => 9999]))->toThrow(ImmutableRecordException::class);
});

test('deleting a journal line is blocked at the Eloquent layer', function () {
    $line = createJournalLine();

    expect(fn () => $line->delete())->toThrow(ImmutableRecordException::class);
});

test('updating a journal line is blocked at the database layer', function () {
    $line = createJournalLine();

    expect(fn () => DB::table('journal_lines')->where('id', $line->id)->update(['debit' => 9999]))
        ->toThrow(QueryException::class);
});

test('deleting a journal line is blocked at the database layer', function () {
    $line = createJournalLine();

    expect(fn () => DB::table('journal_lines')->where('id', $line->id)->delete())
        ->toThrow(QueryException::class);
});
