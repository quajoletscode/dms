<?php

namespace App\Modules\Warehouse\Models;

use App\Casts\MoneyCast;
use App\Casts\QuantityCast;
use App\Support\AppendOnly;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only record of every stock movement. Current stock is always
 * derivable from this table — see StockBalance for the read-speed projection.
 *
 * @property int $id
 * @property string $location_type
 * @property int $location_id
 * @property int $product_id
 * @property int|null $batch_id
 * @property Quantity $qty_in
 * @property Quantity $qty_out
 * @property Money $unit_cost
 * @property string $doc_type
 * @property int $doc_id
 */
class StockLedgerEntry extends Model
{
    use AppendOnly;

    protected $table = 'stock_ledger';

    const UPDATED_AT = null;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_in' => QuantityCast::class,
            'qty_out' => QuantityCast::class,
            'unit_cost' => MoneyCast::class,
            'created_at' => 'datetime',
        ];
    }
}
