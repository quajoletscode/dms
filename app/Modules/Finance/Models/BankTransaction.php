<?php

namespace App\Modules\Finance\Models;

use App\Casts\MoneyCast;
use App\Models\User;
use App\Support\Auditable;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $no
 * @property int $bank_account_id
 * @property string $type
 * @property Money $amount
 * @property Money $charge
 * @property string|null $slip_reference
 * @property int|null $related_bank_account_id
 * @property bool $reconciled
 * @property string|null $description
 * @property int|null $created_by
 * @property Carbon $transacted_at
 * @property-read BankAccount $bankAccount bank_account_id is a required FK — always present once persisted
 */
class BankTransaction extends Model
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
            'charge' => MoneyCast::class,
            'reconciled' => 'boolean',
            'transacted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BankAccount, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * @return BelongsTo<BankAccount, $this>
     */
    public function relatedBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'related_bank_account_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
