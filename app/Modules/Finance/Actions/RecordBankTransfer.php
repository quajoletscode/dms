<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Domain\Exceptions\DuplicateSlipReferenceException;
use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Finance\Models\BankAccount;
use App\Modules\Finance\Models\BankTransaction;
use App\Support\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * One posted journal for the whole transfer, from the transfer_out row; the
 * transfer_in row on the destination account is a non-posting audit
 * companion (posting it too would double the economic event).
 */
final class RecordBankTransfer
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly PostingEngine $postingEngine,
    ) {}

    public function execute(
        BankAccount $fromBankAccount,
        BankAccount $toBankAccount,
        string $amount,
        string $charge = '0',
        ?string $slipReference = null,
    ): BankTransaction {
        Gate::authorize('bank.transfer');

        try {
            return DB::transaction(function () use ($fromBankAccount, $toBankAccount, $amount, $charge, $slipReference) {
                $out = BankTransaction::query()->create([
                    'no' => $this->numbers->next('bank_txn', 'company'),
                    'bank_account_id' => $fromBankAccount->id,
                    'related_bank_account_id' => $toBankAccount->id,
                    'type' => 'transfer_out',
                    'amount' => Money::fromMajor($amount)->minorUnits,
                    'charge' => Money::fromMajor($charge)->minorUnits,
                    'slip_reference' => $slipReference,
                    'created_by' => Auth::id(),
                    'transacted_at' => now(),
                ]);

                BankTransaction::query()->create([
                    'no' => $this->numbers->next('bank_txn', 'company'),
                    'bank_account_id' => $toBankAccount->id,
                    'related_bank_account_id' => $fromBankAccount->id,
                    'type' => 'transfer_in',
                    'amount' => Money::fromMajor($amount)->minorUnits,
                    'created_by' => Auth::id(),
                    'transacted_at' => now(),
                ]);

                $this->postingEngine->post('bank.transferred', $out->load(['bankAccount', 'relatedBankAccount']));

                return $out->refresh();
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateSlipReference($e)) {
                throw new DuplicateSlipReferenceException("Slip reference [{$slipReference}] has already been used.");
            }

            throw $e;
        }
    }

    private function isDuplicateSlipReference(QueryException $e): bool
    {
        // MySQL names the violated key in its message; SQLite reports the
        // bare table.column instead — check for both.
        return str_contains($e->getMessage(), 'bank_transactions_slip_reference_unique')
            || str_contains($e->getMessage(), 'bank_transactions.slip_reference');
    }
}
