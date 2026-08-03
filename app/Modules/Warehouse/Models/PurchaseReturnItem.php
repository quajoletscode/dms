<?php

namespace App\Modules\Warehouse\Models;

use App\Casts\MoneyCast;
use App\Casts\QuantityCast;
use App\Modules\MasterData\Models\Batch;
use App\Modules\MasterData\Models\Product;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $purchase_return_id
 * @property int $grn_item_id
 * @property int $product_id
 * @property int|null $batch_id
 * @property Quantity $qty_returned
 * @property Money $unit_cost
 */
class PurchaseReturnItem extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_returned' => QuantityCast::class,
            'unit_cost' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<PurchaseReturn, $this>
     */
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    /**
     * @return BelongsTo<GrnItem, $this>
     */
    public function grnItem(): BelongsTo
    {
        return $this->belongsTo(GrnItem::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Batch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
