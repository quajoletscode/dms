<?php

namespace App\Modules\Sales\Models;

use App\Casts\MoneyCast;
use App\Models\User;
use App\Modules\MasterData\Models\Customer;
use App\Modules\Warehouse\Models\Warehouse;
use App\Support\Auditable;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $no
 * @property int $customer_id
 * @property int $warehouse_id
 * @property string $status
 * @property Money $subtotal
 * @property Money $tax_total
 * @property Money $discount_total
 * @property Money $grand_total
 * @property int|null $created_by
 * @property-read Customer $customer customer_id is a required FK — always present once persisted
 * @property-read Warehouse $warehouse warehouse_id is a required FK — always present once persisted
 */
class SalesOrder extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => MoneyCast::class,
            'tax_total' => MoneyCast::class,
            'discount_total' => MoneyCast::class,
            'grand_total' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<SalesOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }
}
