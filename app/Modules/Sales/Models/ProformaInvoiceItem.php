<?php

namespace App\Modules\Sales\Models;

use App\Casts\MoneyCast;
use App\Casts\QuantityCast;
use App\Modules\MasterData\Models\Product;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $proforma_invoice_id
 * @property int $product_id
 * @property Quantity $qty
 * @property Money $unit_price
 * @property Money $discount
 * @property Money $tax
 * @property-read Product $product product_id is a required FK — always present once persisted
 */
class ProformaInvoiceItem extends Model
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
            'tax' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<ProformaInvoice, $this>
     */
    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
