<?php

namespace App\Modules\Warehouse\Models;

use App\Models\User;
use App\Modules\MasterData\Models\Supplier;
use App\Support\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $no
 * @property int|null $po_id
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property string|null $invoice_ref
 * @property string $status
 * @property int|null $posted_by
 */
class Grn extends Model
{
    use Auditable;

    protected $table = 'grns';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * @return HasMany<GrnItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GrnItem::class);
    }
}
