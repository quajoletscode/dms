<?php

namespace App\Modules\Sales\Models;

use App\Casts\MoneyCast;
use App\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use App\Support\Auditable;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $warehouse_id
 * @property int $user_id
 * @property Money $opening_float
 * @property Money|null $closed_float
 * @property Money|null $expected_float
 * @property Money|null $variance
 * @property string $status
 * @property Carbon $opened_at
 * @property Carbon|null $closed_at
 * @property-read User $user user_id is a required FK — always present once persisted
 */
class TillSession extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_float' => MoneyCast::class,
            'closed_float' => MoneyCast::class,
            'expected_float' => MoneyCast::class,
            'variance' => MoneyCast::class,
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<TillCashMovement, $this>
     */
    public function cashMovements(): HasMany
    {
        return $this->hasMany(TillCashMovement::class);
    }

    /**
     * @return HasMany<InvoicePayment, $this>
     */
    public function invoicePayments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }
}
