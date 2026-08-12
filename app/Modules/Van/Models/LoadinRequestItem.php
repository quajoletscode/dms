<?php

namespace App\Modules\Van\Models;

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
 * @property int $loadin_request_id
 * @property int $product_id
 * @property int|null $batch_id
 * @property Quantity $qty_good
 * @property Quantity $qty_damaged
 * @property Money $unit_cost
 * @property-read Product $product product_id is a required FK — always present once persisted
 */
class LoadinRequestItem extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_good' => QuantityCast::class,
            'qty_damaged' => QuantityCast::class,
            'unit_cost' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<LoadinRequest, $this>
     */
    public function loadinRequest(): BelongsTo
    {
        return $this->belongsTo(LoadinRequest::class);
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
