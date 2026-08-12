<?php

namespace App\Modules\Van\Models;

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
 * @property int $van_storage_id
 * @property int|null $dsr_user_id
 * @property Carbon $settlement_date
 * @property Money $opening_value
 * @property Money $loadout_value
 * @property Money $sales_value
 * @property Money $loadin_value
 * @property Money $closing_value
 * @property Money $cash_expected
 * @property Money|null $cash_counted
 * @property Money|null $cash_variance
 * @property string $status
 * @property-read VanStorage $vanStorage van_storage_id is a required FK — always present once persisted
 */
class DsrSettlement extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settlement_date' => 'date',
            'opening_value' => MoneyCast::class,
            'loadout_value' => MoneyCast::class,
            'sales_value' => MoneyCast::class,
            'loadin_value' => MoneyCast::class,
            'closing_value' => MoneyCast::class,
            'cash_expected' => MoneyCast::class,
            'cash_counted' => MoneyCast::class,
            'cash_variance' => MoneyCast::class,
            'generated_at' => 'datetime',
            'signed_off_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
