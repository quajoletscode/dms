<?php

namespace App\Modules\Finance\Models;

use App\Casts\MoneyCast;
use App\Support\Auditable;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $account_no
 * @property string $bank_name
 * @property int $coa_account_id
 * @property Money $opening_balance
 * @property bool $is_active
 * @property-read ChartOfAccount $account coa_account_id is a required FK — always present once persisted
 */
class BankAccount extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_balance' => MoneyCast::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_account_id');
    }

    /**
     * @return HasMany<BankTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }
}
