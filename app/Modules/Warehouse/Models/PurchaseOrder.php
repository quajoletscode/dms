<?php

namespace App\Modules\Warehouse\Models;

use App\Casts\MoneyCast;
use App\Models\User;
use App\Modules\MasterData\Models\Supplier;
use App\Support\Auditable;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $no
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property string $status
 * @property Carbon|null $expected_delivery_date
 * @property Money $subtotal
 * @property Money $tax_total
 * @property Money $discount_total
 * @property Money $grand_total
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property-read Supplier $supplier supplier_id is a required FK — always present once persisted
 * @property-read Warehouse $warehouse warehouse_id is a required FK — always present once persisted
 */
class PurchaseOrder extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_delivery_date' => 'date',
            'subtotal' => MoneyCast::class,
            'tax_total' => MoneyCast::class,
            'discount_total' => MoneyCast::class,
            'grand_total' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
