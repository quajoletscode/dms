<?php

namespace App\Modules\Sales\Models;

use App\Casts\MoneyCast;
use App\Casts\QuantityCast;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\MasterData\Models\Batch;
use App\Modules\MasterData\Models\Product;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property string $line_type item (stock product, the default), gl_account (non-stock charge posted straight to a ledger account), or comment (descriptive text, no amount)
 * @property int|null $product_id required when line_type is item, null otherwise
 * @property int|null $gl_account_id set when line_type is gl_account, null otherwise
 * @property int|null $batch_id
 * @property Carbon|null $service_date
 * @property string|null $vehicle_no
 * @property string|null $line_description
 * @property Quantity $qty
 * @property Money $unit_price
 * @property Money $discount
 * @property string $tax_rate
 * @property Money $tax
 * @property Money $unit_cost
 * @property-read Product|null $product
 * @property-read ChartOfAccount|null $glAccount
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
            'service_date' => 'date',
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

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }
}
