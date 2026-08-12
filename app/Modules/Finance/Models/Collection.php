<?php

namespace App\Modules\Finance\Models;

use App\Casts\MoneyCast;
use App\Models\User;
use App\Modules\MasterData\Models\Customer;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Van\Models\VanStorage;
use App\Support\Auditable;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * BNK-06: with_dsr -> handed_over -> deposited, traced to a person at every
 * hop (dsr_user_id / handed_over_by / the depositing bank_transaction's
 * creator).
 *
 * @property int $id
 * @property string $no
 * @property int $van_storage_id
 * @property int $warehouse_id
 * @property int $dsr_user_id
 * @property int $customer_id
 * @property int $invoice_id
 * @property Money $amount
 * @property string $status
 * @property Carbon $collected_at
 * @property int|null $handed_over_by
 * @property Carbon|null $handed_over_at
 * @property int|null $bank_transaction_id
 * @property Carbon|null $deposited_at
 * @property-read VanStorage $vanStorage van_storage_id is a required FK — always present once persisted
 * @property-read Customer $customer customer_id is a required FK — always present once persisted
 * @property-read Invoice $invoice invoice_id is a required FK — always present once persisted
 */
class Collection extends Model
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
            'collected_at' => 'datetime',
            'handed_over_at' => 'datetime',
            'deposited_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<VanStorage, $this>
     */
    public function vanStorage(): BelongsTo
    {
        return $this->belongsTo(VanStorage::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dsr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dsr_user_id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by');
    }

    /**
     * @return BelongsTo<BankTransaction, $this>
     */
    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }
}
