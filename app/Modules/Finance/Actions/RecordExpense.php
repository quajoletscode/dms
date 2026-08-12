<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\ExpenseCategory;
use App\Support\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class RecordExpense
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly PostingEngine $postingEngine,
    ) {}

    public function execute(
        int $expenseCategoryId,
        string $amount,
        ?int $warehouseId = null,
        ?int $paidFromBankAccountId = null,
        ?string $description = null,
    ): Expense {
        Gate::authorize('expense.record');

        return DB::transaction(function () use ($expenseCategoryId, $amount, $warehouseId, $paidFromBankAccountId, $description) {
            $category = ExpenseCategory::query()->whereKey($expenseCategoryId)->firstOrFail();

            $expense = Expense::query()->create([
                'no' => $this->numbers->next('expense', 'company'),
                'expense_category_id' => $category->id,
                'warehouse_id' => $warehouseId,
                'paid_from_bank_account_id' => $paidFromBankAccountId,
                'amount' => Money::fromMajor($amount)->minorUnits,
                'description' => $description,
                'created_by' => Auth::id(),
                'expensed_at' => now(),
            ]);

            $this->postingEngine->post('expense.recorded', $expense->load(['category', 'paidFromBankAccount']));

            return $expense->refresh();
        });
    }
}
