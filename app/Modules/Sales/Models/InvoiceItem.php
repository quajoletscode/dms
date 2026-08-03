<?php

namespace App\Modules\Sales\Models;

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
 * @property int $invoice_id
 * @property int $product_id
 * @property int|null $batch_id
 * @property Quantity $qty
 * @property Money $unit_price
 * @property Money $discount
 * @property string $tax_rate
 * @property Money $tax
 * @property Money $unit_cost
 * @property-read Product $product product_id is a required FK — always present once persisted
 */
class InvoiceItem extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => QuantityCast::class,
            'unit_price' => MoneyCast::class,
            'discount' => MoneyCast::class,
            'tax_rate' => 'decimal:2',
            'tax' => MoneyCast::class,
            'unit_cost' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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
