<?php

namespace App\Modules\Warehouse\Models;

use App\Casts\QuantityCast;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;

/**
 * A read-speed projection of stock_ledger, keyed by (location, product,
 * batch). Never authoritative — rebuildable via `php artisan stock:rebuild-balances`.
 *
 * @property int $id
 * @property string $location_type
 * @property int $location_id
 * @property int $product_id
 * @property int $batch_id
 * @property Quantity $qty_on_hand
 */
class StockBalance extends Model
{
    const CREATED_AT = null;

    protected $table = 'stock_balances';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_on_hand' => QuantityCast::class,
        ];
    }
}
