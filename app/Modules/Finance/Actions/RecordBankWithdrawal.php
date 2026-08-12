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

final class RecordBankWithdrawal
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly PostingEngine $postingEngine,
    ) {}

    public function execute(BankAccount $bankAccount, string $amount, ?string $slipReference = null, ?string $description = null): BankTransaction
    {
        Gate::authorize('bank.withdraw');

        try {
            return DB::transaction(function () use ($bankAccount, $amount, $slipReference, $description) {
                $transaction = BankTransaction::query()->create([
                    'no' => $this->numbers->next('bank_txn', 'company'),
                    'bank_account_id' => $bankAccount->id,
                    'type' => 'withdrawal',
                    'amount' => Money::fromMajor($amount)->minorUnits,
                    'slip_reference' => $slipReference,
                    'description' => $description,
                    'created_by' => Auth::id(),
                    'transacted_at' => now(),
                ]);

                $this->postingEngine->post('bank.withdrawn', $transaction->load('bankAccount'));

                return $transaction->refresh();
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
