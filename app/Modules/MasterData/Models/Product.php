<?php

namespace App\Modules\MasterData\Models;

use App\Casts\MoneyCast;
use App\Casts\QuantityCast;
use App\Support\Auditable;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $sku
 * @property string|null $barcode
 * @property string $name
 * @property int|null $category_id
 * @property int $unit_id
 * @property Money $cost_price
 * @property Money $wholesale_price
 * @property Money $retail_price
 * @property Money $van_price
 * @property string $tax_rate
 * @property Quantity $reorder_level
 * @property bool $track_expiry
 * @property bool $is_active
 */
class Product extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_price' => MoneyCast::class,
            'wholesale_price' => MoneyCast::class,
            'retail_price' => MoneyCast::class,
            'van_price' => MoneyCast::class,
            'reorder_level' => QuantityCast::class,
            'track_expiry' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return HasMany<Batch, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }
}
