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
 * @property int $loadout_request_id
 * @property int $product_id
 * @property int|null $batch_id
 * @property Quantity $qty_requested
 * @property Quantity|null $qty_approved
 * @property Quantity|null $qty_loaded
 * @property Quantity|null $qty_received
 * @property Money $unit_cost
 * @property-read Product $product product_id is a required FK — always present once persisted
 */
class LoadoutRequestItem extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_requested' => QuantityCast::class,
            'qty_approved' => QuantityCast::class,
            'qty_loaded' => QuantityCast::class,
            'qty_received' => QuantityCast::class,
            'unit_cost' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<LoadoutRequest, $this>
     */
    public function loadoutRequest(): BelongsTo
    {
        return $this->belongsTo(LoadoutRequest::class);
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
     * Loaded minus received — recorded, never silently absorbed, per
     * LoadoutPartialConfirmationDiscrepancyTest.
     */
    public function discrepancy(): Quantity
    {
        $loaded = $this->qty_loaded ?? Quantity::zero();
        $received = $this->qty_received ?? Quantity::zero();

        return $loaded->subtract($received);
    }
}
