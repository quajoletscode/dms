<?php

namespace App\Modules\Warehouse\Models;

use App\Casts\MoneyCast;
use App\Casts\QuantityCast;
use App\Modules\MasterData\Models\Product;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $product_id
 * @property Quantity $qty_ordered
 * @property Quantity $qty_received
 * @property Money $unit_cost
 * @property Money $discount
 * @property Money $tax
 * @property-read Product $product product_id is a required FK — always present once persisted
 */
class PurchaseOrderItem extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_ordered' => QuantityCast::class,
            'qty_received' => QuantityCast::class,
            'unit_cost' => MoneyCast::class,
            'discount' => MoneyCast::class,
            'tax' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
