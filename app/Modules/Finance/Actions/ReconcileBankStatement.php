<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\BankTransaction;
use Illuminate\Support\Facades\Gate;

final class ReconcileBankStatement
{
    /**
     * @param  list<int>  $bankTransactionIds
     * @return int number of transactions marked reconciled
     */
    public function execute(array $bankTransactionIds): int
    {
        Gate::authorize('bank.reconcile');

        return BankTransaction::query()->whereIn('id', $bankTransactionIds)->update(['reconciled' => true]);
    }
}
