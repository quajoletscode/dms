<?php

namespace App\Modules\Finance\Models;

use App\Casts\MoneyCast;
use App\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use App\Support\Auditable;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $no
 * @property int $expense_category_id
 * @property int|null $warehouse_id
 * @property int|null $paid_from_bank_account_id
 * @property Money $amount
 * @property string|null $description
 * @property int|null $created_by
 * @property Carbon $expensed_at
 * @property-read ExpenseCategory $category expense_category_id is a required FK — always present once persisted
 */
class Expense extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'expensed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<BankAccount, $this>
     */
    public function paidFromBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'paid_from_bank_account_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
